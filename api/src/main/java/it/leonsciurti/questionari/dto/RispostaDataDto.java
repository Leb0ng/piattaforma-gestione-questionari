package it.leonsciurti.questionari.dto;

import java.util.List;

import it.leonsciurti.questionari.modello.TipologiaDomanda;

// una domanda con quello che l'utente aveva risposto:
// testo per le APERTE (null se lasciata vuota), opzioni con il flag "scelta" per le altre
public record RispostaDataDto(Long domandaId, String domanda, TipologiaDomanda tipologia, boolean obbligatoria,
		String testo, List<OpzioneSceltaDto> opzioni) {
}
