package it.leonsciurti.questionari.dto;

import java.util.List;

import it.leonsciurti.questionari.modello.Canale;
import jakarta.validation.Valid;
import jakarta.validation.constraints.NotNull;

// tutto quello che il client manda quando l'utente preme "Invia"
public record InvioCompilazioneRequest(
		@NotNull(message = "Il canale è obbligatorio (WEB o MOBILE)") Canale canale,
		@NotNull(message = "Mancano le risposte") @Valid List<RispostaRequest> risposte) {
}
