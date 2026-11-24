package it.leonsciurti.questionari.modello;

import java.time.LocalDateTime;

import jakarta.persistence.Entity;
import jakarta.persistence.GeneratedValue;
import jakarta.persistence.GenerationType;
import jakarta.persistence.Id;
import jakarta.persistence.Table;

// Una riga della tabella "token_api": il "pass" che diamo all'utente dopo il login
@Entity
@Table(name = "token_api")
public class TokenApi {

	@Id
	@GeneratedValue(strategy = GenerationType.IDENTITY)
	private Long id;

	private Long utenteId;

	private String token;

	private LocalDateTime scadeIl;

	protected TokenApi() {
	}

	public TokenApi(Long utenteId, String token, LocalDateTime scadeIl) {
		this.utenteId = utenteId;
		this.token = token;
		this.scadeIl = scadeIl;
	}

	public Long getId() {
		return id;
	}

	public Long getUtenteId() {
		return utenteId;
	}

	public String getToken() {
		return token;
	}

	public LocalDateTime getScadeIl() {
		return scadeIl;
	}
}
