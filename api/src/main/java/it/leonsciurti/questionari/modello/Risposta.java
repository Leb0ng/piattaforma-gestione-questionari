package it.leonsciurti.questionari.modello;

import jakarta.persistence.Entity;
import jakarta.persistence.GeneratedValue;
import jakarta.persistence.GenerationType;
import jakarta.persistence.Id;
import jakarta.persistence.Table;

// Una riga della tabella "risposte".
//   domanda APERTA   -> testo pieno, opzioneId null
//   domanda SINGOLA  -> una riga con opzioneId
//   domanda MULTIPLA -> una riga per ogni opzione scelta
@Entity
@Table(name = "risposte")
public class Risposta {

	@Id
	@GeneratedValue(strategy = GenerationType.IDENTITY)
	private Long id;

	private Long compilazioneId;

	private Long domandaId;

	private Long opzioneId;

	private String testo;

	protected Risposta() {
	}

	public Risposta(Long compilazioneId, Long domandaId, Long opzioneId, String testo) {
		this.compilazioneId = compilazioneId;
		this.domandaId = domandaId;
		this.opzioneId = opzioneId;
		this.testo = testo;
	}

	public Long getId() {
		return id;
	}

	public Long getCompilazioneId() {
		return compilazioneId;
	}

	public Long getDomandaId() {
		return domandaId;
	}

	public Long getOpzioneId() {
		return opzioneId;
	}

	public String getTesto() {
		return testo;
	}
}
