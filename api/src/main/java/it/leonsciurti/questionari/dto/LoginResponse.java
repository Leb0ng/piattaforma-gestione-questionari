package it.leonsciurti.questionari.dto;

// risposta al login: il token da rimandare ad ogni richiesta + chi sei
public record LoginResponse(String token, UtenteDto utente) {
}
