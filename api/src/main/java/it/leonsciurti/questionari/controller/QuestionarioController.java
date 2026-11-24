package it.leonsciurti.questionari.controller;

import java.util.List;

import org.springframework.http.HttpStatus;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestAttribute;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.ResponseStatus;
import org.springframework.web.bind.annotation.RestController;

import it.leonsciurti.questionari.dto.CompilazioneRiepilogoDto;
import it.leonsciurti.questionari.dto.InvioCompilazioneRequest;
import it.leonsciurti.questionari.dto.QuestionarioDettaglioDto;
import it.leonsciurti.questionari.dto.QuestionarioRiepilogoDto;
import it.leonsciurti.questionari.modello.Utente;
import it.leonsciurti.questionari.servizio.CompilazioneService;
import it.leonsciurti.questionari.servizio.QuestionarioService;
import it.leonsciurti.questionari.sicurezza.AuthInterceptor;
import jakarta.validation.Valid;

// @RequestAttribute(AuthInterceptor.UTENTE) = "dammi l'utente che l'interceptor
// ha riconosciuto dal token". Non arriva MAI dal client.
@RestController
@RequestMapping("/api/questionari")
public class QuestionarioController {

	private final QuestionarioService questionarioService;
	private final CompilazioneService compilazioneService;

	public QuestionarioController(QuestionarioService questionarioService, CompilazioneService compilazioneService) {
		this.questionarioService = questionarioService;
		this.compilazioneService = compilazioneService;
	}

	// GET /api/questionari -> elenco dei questionari pubblicati
	@GetMapping
	public List<QuestionarioRiepilogoDto> elenco(@RequestAttribute(AuthInterceptor.UTENTE) Utente utente) {
		return questionarioService.elencoPubblicati(utente);
	}

	// GET /api/questionari/5 -> dettaglio con domande e opzioni
	@GetMapping("/{id}")
	public QuestionarioDettaglioDto dettaglio(@RequestAttribute(AuthInterceptor.UTENTE) Utente utente,
			@PathVariable Long id) {
		return questionarioService.dettaglio(id, utente);
	}

	// POST /api/questionari/5/compilazioni -> invio delle risposte (201 = creato)
	@PostMapping("/{id}/compilazioni")
	@ResponseStatus(HttpStatus.CREATED)
	public CompilazioneRiepilogoDto invia(@RequestAttribute(AuthInterceptor.UTENTE) Utente utente,
			@PathVariable Long id, @Valid @RequestBody InvioCompilazioneRequest richiesta) {
		return compilazioneService.invia(utente, id, richiesta);
	}
}
