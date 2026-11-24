package it.leonsciurti.questionari.dto;

import java.util.List;

// il questionario completo, pronto da mostrare nel form di compilazione
public record QuestionarioDettaglioDto(Long id, String titolo, String descrizione, List<DomandaDto> domande) {
}
