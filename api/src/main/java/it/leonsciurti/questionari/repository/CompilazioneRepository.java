package it.leonsciurti.questionari.repository;

import java.util.List;
import java.util.Optional;

import org.springframework.data.jpa.repository.JpaRepository;

import it.leonsciurti.questionari.modello.Compilazione;

public interface CompilazioneRepository extends JpaRepository<Compilazione, Long> {

	// questo utente ha gia compilato questo questionario?
	boolean existsByQuestionarioIdAndUtenteId(Long questionarioId, Long utenteId);

	// lo storico di un utente, dalla piu recente
	List<Compilazione> findByUtenteIdOrderByInviataIlDesc(Long utenteId);

	// cerco per id MA anche per utente: cosi nessuno puo leggere le compilazioni degli altri
	Optional<Compilazione> findByIdAndUtenteId(Long id, Long utenteId);
}
