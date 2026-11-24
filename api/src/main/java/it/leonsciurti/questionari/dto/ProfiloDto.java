package it.leonsciurti.questionari.dto;

import java.time.LocalDate;

import it.leonsciurti.questionari.modello.AreaGeografica;
import it.leonsciurti.questionari.modello.FasciaEta;
import it.leonsciurti.questionari.modello.Occupazione;
import it.leonsciurti.questionari.modello.Sesso;

// il profilo dell'utente autenticato, come lo mostra la pagina "Profilo".
// I campi non ancora indicati arrivano null; fasciaEta e' calcolata dalla data di nascita.
public record ProfiloDto(String username, String nome, Sesso sesso, LocalDate dataNascita, FasciaEta fasciaEta,
		AreaGeografica areaGeografica, Occupazione occupazione, boolean completo) {
}
