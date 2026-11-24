package it.leonsciurti.questionari.dto;

import java.time.LocalDateTime;

// una riga dell'elenco dei questionari pubblicati.
// giaCompilato dice al client se mostrare "Compila" oppure "Gia compilato"
public record QuestionarioRiepilogoDto(Long id, String titolo, String descrizione, LocalDateTime pubblicatoIl,
		boolean giaCompilato) {
}
