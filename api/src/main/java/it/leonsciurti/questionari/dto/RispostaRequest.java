package it.leonsciurti.questionari.dto;

import java.util.List;

import jakarta.validation.constraints.NotNull;

// la risposta a UNA domanda, come la manda il client:
//   APERTA   -> testo
//   SINGOLA  -> opzioniIds con un solo id
//   MULTIPLA -> opzioniIds con uno o piu id
public record RispostaRequest(
		@NotNull(message = "Manca l'id della domanda") Long domandaId,
		String testo,
		List<Long> opzioniIds) {
}
