package it.leonsciurti.questionari.modello;

import java.time.LocalDateTime;

import jakarta.persistence.Entity;
import jakarta.persistence.EnumType;
import jakarta.persistence.Enumerated;
import jakarta.persistence.GeneratedValue;
import jakarta.persistence.GenerationType;
import jakarta.persistence.Id;
import jakarta.persistence.Table;

// Una riga della tabella "compilazioni": "l'utente X ha compilato il questionario Y".
// E' la "busta" che contiene le risposte.
@Entity
@Table(name = "compilazioni")
public class Compilazione {

	@Id
	@GeneratedValue(strategy = GenerationType.IDENTITY)
	private Long id;

	private Long questionarioId;

	private Long utenteId;

	@Enumerated(EnumType.STRING)
	private Canale canale;

	private LocalDateTime inviataIl;

	protected Compilazione() {
	}

	public Compilazione(Long questionarioId, Long utenteId, Canale canale, LocalDateTime inviataIl) {
		this.questionarioId = questionarioId;
		this.utenteId = utenteId;
		this.canale = canale;
		this.inviataIl = inviataIl;
	}

	public Long getId() {
		return id;
	}

	public Long getQuestionarioId() {
		return questionarioId;
	}

	public Long getUtenteId() {
		return utenteId;
	}

	public Canale getCanale() {
		return canale;
	}

	public LocalDateTime getInviataIl() {
		return inviataIl;
	}
}
