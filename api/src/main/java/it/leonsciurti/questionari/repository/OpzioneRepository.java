package it.leonsciurti.questionari.repository;

import java.util.Collection;
import java.util.List;

import org.springframework.data.jpa.repository.JpaRepository;

import it.leonsciurti.questionari.modello.Opzione;

public interface OpzioneRepository extends JpaRepository<Opzione, Long> {

	// tutte le opzioni di un gruppo di domande in un colpo solo (WHERE domanda_id IN (...))
	List<Opzione> findByDomandaIdInOrderByOrdineAscIdAsc(Collection<Long> domandaIds);
}
