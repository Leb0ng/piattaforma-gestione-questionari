package it.leonsciurti.questionari.repository;

import java.util.List;

import org.springframework.data.jpa.repository.JpaRepository;

import it.leonsciurti.questionari.modello.Domanda;

public interface DomandaRepository extends JpaRepository<Domanda, Long> {

	// le domande di un questionario, nell'ordine deciso dall'admin
	List<Domanda> findByQuestionarioIdOrderByOrdineAscIdAsc(Long questionarioId);
}
