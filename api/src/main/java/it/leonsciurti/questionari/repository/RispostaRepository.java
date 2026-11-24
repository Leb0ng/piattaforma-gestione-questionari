package it.leonsciurti.questionari.repository;

import java.util.List;

import org.springframework.data.jpa.repository.JpaRepository;

import it.leonsciurti.questionari.modello.Risposta;

public interface RispostaRepository extends JpaRepository<Risposta, Long> {

	List<Risposta> findByCompilazioneId(Long compilazioneId);
}
