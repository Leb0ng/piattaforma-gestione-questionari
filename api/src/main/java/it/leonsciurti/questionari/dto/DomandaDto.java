package it.leonsciurti.questionari.dto;

import java.util.List;

import it.leonsciurti.questionari.modello.TipologiaDomanda;

// una domanda con le sue opzioni (lista vuota se la domanda e' APERTA)
public record DomandaDto(Long id, String testo, TipologiaDomanda tipologia, boolean obbligatoria,
		List<OpzioneDto> opzioni) {
}
