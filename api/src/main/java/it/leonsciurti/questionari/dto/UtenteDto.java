package it.leonsciurti.questionari.dto;

// i dati dell'utente che possono uscire dalle API (niente hash della password!)
// profiloCompleto = false -> il client chiede di completare il profilo
public record UtenteDto(Long id, String username, String nome, boolean profiloCompleto) {
}
