package it.leonsciurti.questionari.dto;

import java.time.LocalDateTime;
import java.util.List;

import it.leonsciurti.questionari.modello.Canale;
import it.leonsciurti.questionari.modello.StatoQuestionario;

// una compilazione gia inviata, da rivedere in sola lettura
public record CompilazioneDettaglioDto(Long id, Long questionarioId, String titolo, String descrizione,
		StatoQuestionario statoQuestionario, Canale canale, LocalDateTime inviataIl,
		List<RispostaDataDto> risposte) {
}
