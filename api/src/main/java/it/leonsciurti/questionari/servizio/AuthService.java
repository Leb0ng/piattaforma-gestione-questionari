package it.leonsciurti.questionari.servizio;

import java.security.SecureRandom;
import java.time.LocalDateTime;
import java.util.HexFormat;

import org.springframework.http.HttpStatus;
import org.springframework.security.crypto.bcrypt.BCryptPasswordEncoder;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import it.leonsciurti.questionari.dto.LoginRequest;
import it.leonsciurti.questionari.dto.LoginResponse;
import it.leonsciurti.questionari.dto.UtenteDto;
import it.leonsciurti.questionari.errori.ApiException;
import it.leonsciurti.questionari.modello.Ruolo;
import it.leonsciurti.questionari.modello.TokenApi;
import it.leonsciurti.questionari.modello.Utente;
import it.leonsciurti.questionari.repository.TokenApiRepository;
import it.leonsciurti.questionari.repository.UtenteRepository;

// Tutta la logica di accesso: login, riconoscimento del token, logout
@Service
public class AuthService {

	// dopo quante ore il token smette di valere
	private static final int ORE_VALIDITA_TOKEN = 8;

	private final UtenteRepository utenteRepository;
	private final TokenApiRepository tokenRepository;

	// lo stesso algoritmo (bcrypt) che usa password_hash() di PHP
	private final BCryptPasswordEncoder encoder = new BCryptPasswordEncoder();
	private final SecureRandom random = new SecureRandom();

	public AuthService(UtenteRepository utenteRepository, TokenApiRepository tokenRepository) {
		this.utenteRepository = utenteRepository;
		this.tokenRepository = tokenRepository;
	}

	@Transactional
	public LoginResponse login(LoginRequest richiesta) {
		Utente utente = utenteRepository.findByUsername(richiesta.username().trim()).orElse(null);

		// stesso messaggio sia se lo username non esiste sia se la password e' sbagliata:
		// cosi non regalo a nessuno l'informazione "questo username esiste"
		if (utente == null || !encoder.matches(richiesta.password(), utente.getPasswordHash())) {
			throw new ApiException(HttpStatus.UNAUTHORIZED, "Username o password non corretti");
		}

		// portale e app sono per gli utenti: l'admin lavora dal Back Office
		if (utente.getRuolo() != Ruolo.UTENTE) {
			throw new ApiException(HttpStatus.FORBIDDEN, "L'amministratore accede dal Back Office");
		}

		String token = nuovoToken();
		tokenRepository.save(new TokenApi(utente.getId(), token,
				LocalDateTime.now().plusHours(ORE_VALIDITA_TOKEN)));

		return new LoginResponse(token, new UtenteDto(utente.getId(), utente.getUsername(), utente.getNome(),
				utente.isProfiloCompleto()));
	}

	// usato dall'interceptor ad ogni richiesta: dal token risalgo all'utente
	@Transactional(readOnly = true)
	public Utente utenteDaToken(String token) {
		TokenApi trovato = tokenRepository.findByToken(token)
				.orElseThrow(() -> new ApiException(HttpStatus.UNAUTHORIZED,
						"Sessione non valida: effettua di nuovo il login"));

		if (trovato.getScadeIl().isBefore(LocalDateTime.now())) {
			throw new ApiException(HttpStatus.UNAUTHORIZED, "Sessione scaduta: effettua di nuovo il login");
		}

		return utenteRepository.findById(trovato.getUtenteId())
				.orElseThrow(() -> new ApiException(HttpStatus.UNAUTHORIZED,
						"Sessione non valida: effettua di nuovo il login"));
	}

	// logout = cancello il token dal db, da quel momento non vale piu
	@Transactional
	public void logout(String token) {
		tokenRepository.findByToken(token).ifPresent(tokenRepository::delete);
	}

	// 32 byte casuali scritti in esadecimale = 64 caratteri impossibili da indovinare
	private String nuovoToken() {
		byte[] bytes = new byte[32];
		random.nextBytes(bytes);
		return HexFormat.of().formatHex(bytes);
	}
}
