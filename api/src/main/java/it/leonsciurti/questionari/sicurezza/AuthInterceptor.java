package it.leonsciurti.questionari.sicurezza;

import org.springframework.http.HttpStatus;
import org.springframework.stereotype.Component;
import org.springframework.web.servlet.HandlerInterceptor;

import it.leonsciurti.questionari.errori.ApiException;
import it.leonsciurti.questionari.modello.Utente;
import it.leonsciurti.questionari.servizio.AuthService;
import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpServletResponse;

// Il "buttafuori" delle API: gira PRIMA di ogni endpoint protetto.
// Legge il token dall'header, chiede al db di chi e', e attacca l'utente alla richiesta.
// E' qui che rispetto la regola della traccia "senza fidarsi degli ID trasmessi dal client":
// chi sei lo decide il token, mai un id scritto nella richiesta.
@Component
public class AuthInterceptor implements HandlerInterceptor {

	// nome con cui salvo l'utente dentro la richiesta, i controller lo rileggono da qui
	public static final String UTENTE = "utenteAutenticato";

	private static final String PREFISSO = "Bearer ";

	private final AuthService authService;

	public AuthInterceptor(AuthService authService) {
		this.authService = authService;
	}

	@Override
	public boolean preHandle(HttpServletRequest request, HttpServletResponse response, Object handler) {
		// le richieste OPTIONS sono il "permesso" che il browser chiede prima di una
		// chiamata da un altro indirizzo (CORS): non hanno token, le lascio passare
		if ("OPTIONS".equalsIgnoreCase(request.getMethod())) {
			return true;
		}

		String header = request.getHeader("Authorization");
		if (header == null || !header.startsWith(PREFISSO)) {
			throw new ApiException(HttpStatus.UNAUTHORIZED, "Devi effettuare il login");
		}

		Utente utente = authService.utenteDaToken(header.substring(PREFISSO.length()).trim());
		request.setAttribute(UTENTE, utente);
		return true;
	}
}
