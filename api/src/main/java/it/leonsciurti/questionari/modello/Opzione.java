package it.leonsciurti.questionari.modello;

import jakarta.persistence.Entity;
import jakarta.persistence.GeneratedValue;
import jakarta.persistence.GenerationType;
import jakarta.persistence.Id;
import jakarta.persistence.Table;

// Una riga della tabella "opzioni" (le scelte delle domande SINGOLA / MULTIPLA)
@Entity
@Table(name = "opzioni")
public class Opzione {

	@Id
	@GeneratedValue(strategy = GenerationType.IDENTITY)
	private Long id;

	private Long domandaId;

	private String testo;

	private int ordine;

	protected Opzione() {
	}

	public Long getId() {
		return id;
	}

	public Long getDomandaId() {
		return domandaId;
	}

	public String getTesto() {
		return testo;
	}

	public int getOrdine() {
		return ordine;
	}
}
