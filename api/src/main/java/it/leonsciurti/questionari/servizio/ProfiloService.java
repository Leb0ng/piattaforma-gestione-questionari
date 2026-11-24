package it.leonsciurti.questionari.servizio;

import java.time.LocalDate;
import java.time.Period;

import org.springframework.http.HttpStatus;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import it.leonsciurti.questionari.dto.ProfiloDto;
import it.leonsciurti.questionari.dto.ProfiloRequest;
import it.leonsciurti.questionari.errori.ApiException;
import it.leonsciurti.questionari.modello.Utente;
import it.leonsciurti.questionari.repository.UtenteRepository;

// Lettura e modifica del profilo dell'utente autenticato.
// L'utente e' sempre quello del token: nessuno puo toccare il profilo di un altro.
@Service
public class ProfiloService {

	// stessi limiti del Back Office (UtentiController)
	private static final int ETA_MINIMA = 14;
	private static final int ETA_MASSIMA = 120;

	private final UtenteRepository utenteRepository;

	public ProfiloService(UtenteRepository utenteRepository) {
		this.utenteRepository = utenteRepository;
	}

	@Transactional(readOnly = true)
	public ProfiloDto leggi(Utente utente) {
		return dto(ricarica(utente));
	}

	@Transactional
	public ProfiloDto aggiorna(Utente utente, ProfiloRequest richiesta) {
		int anni = Period.between(richiesta.dataNascita(), LocalDate.now()).getYears();
		if (anni < ETA_MINIMA || anni > ETA_MASSIMA) {
			throw new ApiException(HttpStatus.BAD_REQUEST,
					"La data di nascita non è valida: l'età deve essere tra " + ETA_MINIMA + " e " + ETA_MASSIMA + " anni");
		}

		// l'utente dell'interceptor e' stato letto in un'altra transazione:
		// lo rileggo qui, cosi la modifica viene salvata alla fine del metodo
		Utente daAggiornare = ricarica(utente);
		daAggiornare.aggiornaProfilo(richiesta.sesso(), richiesta.dataNascita(), richiesta.areaGeografica(),
				richiesta.occupazione());
		return dto(utenteRepository.save(daAggiornare));
	}

	private Utente ricarica(Utente utente) {
		return utenteRepository.findById(utente.getId())
				.orElseThrow(() -> new ApiException(HttpStatus.UNAUTHORIZED,
						"Sessione non valida: effettua di nuovo il login"));
	}

	private static ProfiloDto dto(Utente u) {
		return new ProfiloDto(u.getUsername(), u.getNome(), u.getSesso(), u.getDataNascita(), u.getFasciaEta(),
				u.getAreaGeografica(), u.getOccupazione(), u.isProfiloCompleto());
	}
}
