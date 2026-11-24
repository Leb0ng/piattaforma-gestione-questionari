package it.leonsciurti.questionari.modello;

import java.time.LocalDateTime;
import java.util.Arrays;

import jakarta.persistence.Entity;
import jakarta.persistence.EnumType;
import jakarta.persistence.Enumerated;
import jakarta.persistence.GeneratedValue;
import jakarta.persistence.GenerationType;
import jakarta.persistence.Id;
import jakarta.persistence.Table;

// Una riga della tabella "questionari".
// Le API lo leggono soltanto: crearlo e cambiargli stato e' compito del Back Office.
@Entity
@Table(name = "questionari")
public class Questionario {

	@Id
	@GeneratedValue(strategy = GenerationType.IDENTITY)
	private Long id;

	private String titolo;

	private String descrizione;

	@Enumerated(EnumType.STRING)
	private StatoQuestionario stato;

	// ----- destinatari -----
	// Colonne SET di MySQL: arrivano come testo con i valori separati da virgola,
	// es. "UOMO" oppure "UNDER_30,OVER_50". Stringa vuota = nessun filtro.
	private String destSesso;

	private String destEta;

	private String destArea;

	private String destOccupazione;

	private LocalDateTime pubblicatoIl;

	private LocalDateTime chiusoIl;

	protected Questionario() {
	}

	public Long getId() {
		return id;
	}

	public String getTitolo() {
		return titolo;
	}

	public String getDescrizione() {
		return descrizione;
	}

	public StatoQuestionario getStato() {
		return stato;
	}

	public LocalDateTime getPubblicatoIl() {
		return pubblicatoIl;
	}

	public LocalDateTime getChiusoIl() {
		return chiusoIl;
	}

	// L'utente rientra tra i destinatari? Deve rientrare in TUTTE le categorie
	// filtrate (AND); dentro una categoria basta uno dei valori scelti (OR).
	public boolean isDestinatario(Utente utente) {
		return ammette(destSesso, utente.getSesso())
				&& ammette(destEta, utente.getFasciaEta())
				&& ammette(destArea, utente.getAreaGeografica())
				&& ammette(destOccupazione, utente.getOccupazione());
	}

	// filtro vuoto -> tutti; altrimenti il valore dell'utente deve essere nell'elenco.
	// Se l'utente non ha indicato quel dato (null) e il filtro c'e', resta fuori.
	private static boolean ammette(String filtro, Enum<?> valore) {
		if (filtro == null || filtro.isBlank()) {
			return true;
		}
		return valore != null && Arrays.asList(filtro.split(",")).contains(valore.name());
	}
}
