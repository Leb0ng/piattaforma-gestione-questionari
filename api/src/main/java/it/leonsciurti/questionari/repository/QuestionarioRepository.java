package it.leonsciurti.questionari.repository;

import java.util.List;

import org.springframework.data.jpa.repository.JpaRepository;

import it.leonsciurti.questionari.modello.Questionario;
import it.leonsciurti.questionari.modello.StatoQuestionario;

public interface QuestionarioRepository extends JpaRepository<Questionario, Long> {

	// i questionari in un certo stato, dal piu recente
	List<Questionario> findByStatoOrderByPubblicatoIlDesc(StatoQuestionario stato);
}
