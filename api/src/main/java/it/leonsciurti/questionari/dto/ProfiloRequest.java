package it.leonsciurti.questionari.dto;

import java.time.LocalDate;

import it.leonsciurti.questionari.modello.AreaGeografica;
import it.leonsciurti.questionari.modello.Occupazione;
import it.leonsciurti.questionari.modello.Sesso;
import jakarta.validation.constraints.NotNull;
import jakarta.validation.constraints.Past;

// quello che il client manda per salvare il profilo: tutte e 4 le categorie.
// Un valore fuori elenco (es. sesso "X") lo respinge gia la lettura del JSON (400).
public record ProfiloRequest(
		@NotNull(message = "Indica il sesso") Sesso sesso,
		@NotNull(message = "Indica la data di nascita") @Past(message = "La data di nascita deve essere nel passato") LocalDate dataNascita,
		@NotNull(message = "Indica l'area geografica") AreaGeografica areaGeografica,
		@NotNull(message = "Indica la condizione lavorativa") Occupazione occupazione) {
}
