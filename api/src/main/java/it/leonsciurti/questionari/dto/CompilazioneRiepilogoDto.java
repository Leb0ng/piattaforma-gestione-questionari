package it.leonsciurti.questionari.dto;

import java.time.LocalDateTime;

import it.leonsciurti.questionari.modello.Canale;
import it.leonsciurti.questionari.modello.StatoQuestionario;

// una riga dello storico "i miei questionari compilati"
public record CompilazioneRiepilogoDto(Long id, Long questionarioId, String titolo,
		StatoQuestionario statoQuestionario, Canale canale, LocalDateTime inviataIl) {
}
