package it.leonsciurti.questionari.dto;

// un'opzione della domanda + se l'utente l'aveva scelta (serve per la vista in sola lettura)
public record OpzioneSceltaDto(Long id, String testo, boolean scelta) {
}
