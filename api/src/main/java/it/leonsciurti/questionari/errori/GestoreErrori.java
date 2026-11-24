package it.leonsciurti.questionari.errori;

import java.util.Map;

import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.http.converter.HttpMessageNotReadableException;
import org.springframework.web.bind.MethodArgumentNotValidException;
import org.springframework.web.bind.annotation.ExceptionHandler;
import org.springframework.web.bind.annotation.RestControllerAdvice;

// Un unico posto dove gli errori diventano risposte JSON tutte uguali:
//   { "errore": "messaggio" }
// cosi portale e app sanno sempre dove leggere il messaggio da mostrare.
@RestControllerAdvice
public class GestoreErrori {

	// gli errori "previsti", lanciati da me nei service
	@ExceptionHandler(ApiException.class)
	public ResponseEntity<Map<String, String>> gestisci(ApiException e) {
		return ResponseEntity.status(e.getStato()).body(Map.of("errore", e.getMessage()));
	}

	// i controlli @NotBlank / @NotNull falliti sui dati in ingresso
	@ExceptionHandler(MethodArgumentNotValidException.class)
	public ResponseEntity<Map<String, String>> gestisci(MethodArgumentNotValidException e) {
		String messaggio = e.getBindingResult().getFieldErrors().stream()
				.map(errore -> errore.getDefaultMessage())
				.findFirst()
				.orElse("Dati non validi");
		return ResponseEntity.status(HttpStatus.BAD_REQUEST).body(Map.of("errore", messaggio));
	}

	// JSON scritto male, o un valore che non esiste (es. canale "FAX")
	@ExceptionHandler(HttpMessageNotReadableException.class)
	public ResponseEntity<Map<String, String>> gestisci(HttpMessageNotReadableException e) {
		return ResponseEntity.status(HttpStatus.BAD_REQUEST)
				.body(Map.of("errore", "Richiesta non valida: controlla il formato dei dati inviati"));
	}
}
