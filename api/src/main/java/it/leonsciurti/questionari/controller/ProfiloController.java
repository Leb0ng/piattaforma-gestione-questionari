package it.leonsciurti.questionari.controller;

import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PutMapping;
import org.springframework.web.bind.annotation.RequestAttribute;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

import it.leonsciurti.questionari.dto.ProfiloDto;
import it.leonsciurti.questionari.dto.ProfiloRequest;
import it.leonsciurti.questionari.modello.Utente;
import it.leonsciurti.questionari.servizio.ProfiloService;
import it.leonsciurti.questionari.sicurezza.AuthInterceptor;
import jakarta.validation.Valid;

// Il profilo dell'utente autenticato (sesso, data di nascita, area, occupazione):
// sono i dati usati per decidere quali questionari puo vedere.
@RestController
@RequestMapping("/api/profilo")
public class ProfiloController {

	private final ProfiloService profiloService;

	public ProfiloController(ProfiloService profiloService) {
		this.profiloService = profiloService;
	}

	// GET /api/profilo -> il mio profilo
	@GetMapping
	public ProfiloDto leggi(@RequestAttribute(AuthInterceptor.UTENTE) Utente utente) {
		return profiloService.leggi(utente);
	}

	// PUT /api/profilo -> salva il mio profilo (tutte e 4 le categorie)
	@PutMapping
	public ProfiloDto aggiorna(@RequestAttribute(AuthInterceptor.UTENTE) Utente utente,
			@Valid @RequestBody ProfiloRequest richiesta) {
		return profiloService.aggiorna(utente, richiesta);
	}
}
