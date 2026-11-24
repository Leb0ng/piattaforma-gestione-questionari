package it.leonsciurti.questionari.modello;

import jakarta.persistence.Entity;
import jakarta.persistence.EnumType;
import jakarta.persistence.Enumerated;
import jakarta.persistence.GeneratedValue;
import jakarta.persistence.GenerationType;
import jakarta.persistence.Id;
import jakarta.persistence.Table;

// Una riga della tabella "domande"
@Entity
@Table(name = "domande")
public class Domanda {

	@Id
	@GeneratedValue(strategy = GenerationType.IDENTITY)
	private Long id;

	// a quale questionario appartiene (colonna questionario_id)
	private Long questionarioId;

	private String testo;

	@Enumerated(EnumType.STRING)
	private TipologiaDomanda tipologia;

	private boolean obbligatoria;

	private int ordine;

	protected Domanda() {
	}

	public Long getId() {
		return id;
	}

	public Long getQuestionarioId() {
		return questionarioId;
	}

	public String getTesto() {
		return testo;
	}

	public TipologiaDomanda getTipologia() {
		return tipologia;
	}

	public boolean isObbligatoria() {
		return obbligatoria;
	}

	public int getOrdine() {
		return ordine;
	}
}
