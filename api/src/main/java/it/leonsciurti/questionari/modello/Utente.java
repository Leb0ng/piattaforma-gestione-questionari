package it.leonsciurti.questionari.modello;

import java.time.LocalDate;

import jakarta.persistence.Entity;
import jakarta.persistence.EnumType;
import jakarta.persistence.Enumerated;
import jakarta.persistence.GeneratedValue;
import jakarta.persistence.GenerationType;
import jakarta.persistence.Id;
import jakarta.persistence.Table;

// Una riga della tabella "utenti".
// Nota: Spring traduce da solo i nomi dei campi da camelCase a snake_case,
// quindi passwordHash qui = colonna password_hash nel db.
@Entity
@Table(name = "utenti")
public class Utente {

	@Id
	@GeneratedValue(strategy = GenerationType.IDENTITY)
	private Long id;

	private String username;

	private String passwordHash;

	private String nome;

	@Enumerated(EnumType.STRING)
	private Ruolo ruolo;

	// ----- profilo: le 4 categorie usate per i destinatari dei questionari -----
	// tutte NULL finche l'utente (o l'admin) non le indica

	@Enumerated(EnumType.STRING)
	private Sesso sesso;

	private LocalDate dataNascita;

	@Enumerated(EnumType.STRING)
	private AreaGeografica areaGeografica;

	@Enumerated(EnumType.STRING)
	private Occupazione occupazione;

	// costruttore vuoto: lo vuole JPA per creare gli oggetti quando legge dal db
	protected Utente() {
	}

	public Long getId() {
		return id;
	}

	public String getUsername() {
		return username;
	}

	public String getPasswordHash() {
		return passwordHash;
	}

	public String getNome() {
		return nome;
	}

	public Ruolo getRuolo() {
		return ruolo;
	}

	public Sesso getSesso() {
		return sesso;
	}

	public LocalDate getDataNascita() {
		return dataNascita;
	}

	public AreaGeografica getAreaGeografica() {
		return areaGeografica;
	}

	public Occupazione getOccupazione() {
		return occupazione;
	}

	// calcolata dalla data di nascita, vedi FasciaEta
	public FasciaEta getFasciaEta() {
		return FasciaEta.di(dataNascita);
	}

	// completo = tutte e 4 le categorie indicate
	public boolean isProfiloCompleto() {
		return sesso != null && dataNascita != null && areaGeografica != null && occupazione != null;
	}

	// l'unica modifica che le API fanno su un utente: il suo profilo
	public void aggiornaProfilo(Sesso sesso, LocalDate dataNascita, AreaGeografica areaGeografica,
			Occupazione occupazione) {
		this.sesso = sesso;
		this.dataNascita = dataNascita;
		this.areaGeografica = areaGeografica;
		this.occupazione = occupazione;
	}
}
