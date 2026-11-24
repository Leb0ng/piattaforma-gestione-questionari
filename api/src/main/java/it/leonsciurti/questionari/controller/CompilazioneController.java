package it.leonsciurti.questionari.controller;

import java.util.List;

import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.RequestAttribute;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

import it.leonsciurti.questionari.dto.CompilazioneDettaglioDto;
import it.leonsciurti.questionari.dto.CompilazioneRiepilogoDto;
import it.leonsciurti.questionari.modello.Utente;
import it.leonsciurti.questionari.servizio.CompilazioneService;
import it.leonsciurti.questionari.sicurezza.AuthInterceptor;

// Lo storico personale: ogni utente vede solo le proprie compilazioni
@RestController
@RequestMapping("/api/compilazioni")
public class CompilazioneController {

	private final CompilazioneService compilazioneService;

	public CompilazioneController(CompilazioneService compilazioneService) {
		this.compilazioneService = compilazioneService;
	}

	// GET /api/compilazioni -> le mie compilazioni
	@GetMapping
	public List<CompilazioneRiepilogoDto> elenco(@RequestAttribute(AuthInterceptor.UTENTE) Utente utente) {
		return compilazioneService.elenco(utente);
	}

	// GET /api/compilazioni/3 -> una mia compilazione con le risposte, in sola lettura
	@GetMapping("/{id}")
	public CompilazioneDettaglioDto dettaglio(@RequestAttribute(AuthInterceptor.UTENTE) Utente utente,
			@PathVariable Long id) {
		return compilazioneService.dettaglio(utente, id);
	}
}
