package it.leonsciurti.questionari.dto;

import jakarta.validation.constraints.NotBlank;

// quello che il client manda per fare login
public record LoginRequest(
		@NotBlank(message = "Lo username è obbligatorio") String username,
		@NotBlank(message = "La password è obbligatoria") String password) {
}
