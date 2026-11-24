package it.leonsciurti.questionari.repository;

import java.util.Optional;

import org.springframework.data.jpa.repository.JpaRepository;

import it.leonsciurti.questionari.modello.Utente;

// Spring scrive da solo la query leggendo il nome del metodo:
// findByUsername -> SELECT * FROM utenti WHERE username = ?
public interface UtenteRepository extends JpaRepository<Utente, Long> {

	Optional<Utente> findByUsername(String username);
}
