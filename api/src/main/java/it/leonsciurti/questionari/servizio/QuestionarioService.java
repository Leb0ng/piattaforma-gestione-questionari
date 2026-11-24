package it.leonsciurti.questionari.servizio;

import java.util.List;
import java.util.Map;
import java.util.Set;
import java.util.stream.Collectors;

import org.springframework.http.HttpStatus;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import it.leonsciurti.questionari.dto.DomandaDto;
import it.leonsciurti.questionari.dto.OpzioneDto;
import it.leonsciurti.questionari.dto.QuestionarioDettaglioDto;
import it.leonsciurti.questionari.dto.QuestionarioRiepilogoDto;
import it.leonsciurti.questionari.errori.ApiException;
import it.leonsciurti.questionari.modello.Compilazione;
import it.leonsciurti.questionari.modello.Domanda;
import it.leonsciurti.questionari.modello.Opzione;
import it.leonsciurti.questionari.modello.Questionario;
import it.leonsciurti.questionari.modello.StatoQuestionario;
import it.leonsciurti.questionari.modello.Utente;
import it.leonsciurti.questionari.repository.CompilazioneRepository;
import it.leonsciurti.questionari.repository.DomandaRepository;
import it.leonsciurti.questionari.repository.OpzioneRepository;
import it.leonsciurti.questionari.repository.QuestionarioRepository;

// Lettura dei questionari: elenco dei pubblicati e dettaglio con domande e opzioni
@Service
public class QuestionarioService {

	private final QuestionarioRepository questionarioRepository;
	private final DomandaRepository domandaRepository;
	private final OpzioneRepository opzioneRepository;
	private final CompilazioneRepository compilazioneRepository;

	public QuestionarioService(QuestionarioRepository questionarioRepository, DomandaRepository domandaRepository,
			OpzioneRepository opzioneRepository, CompilazioneRepository compilazioneRepository) {
		this.questionarioRepository = questionarioRepository;
		this.domandaRepository = domandaRepository;
		this.opzioneRepository = opzioneRepository;
		this.compilazioneRepository = compilazioneRepository;
	}

	// i questionari PUBBLICATI destinati a questo utente, con segnato quali ha gia compilato.
	// Quelli riservati ad altre categorie di utenti non compaiono proprio.
	// Uno gia compilato resta in elenco anche se nel frattempo il profilo e' cambiato.
	@Transactional(readOnly = true)
	public List<QuestionarioRiepilogoDto> elencoPubblicati(Utente utente) {
		Set<Long> giaCompilati = compilazioneRepository.findByUtenteIdOrderByInviataIlDesc(utente.getId()).stream()
				.map(Compilazione::getQuestionarioId)
				.collect(Collectors.toSet());

		return questionarioRepository.findByStatoOrderByPubblicatoIlDesc(StatoQuestionario.PUBBLICATO).stream()
				.filter(q -> q.isDestinatario(utente) || giaCompilati.contains(q.getId()))
				.map(q -> new QuestionarioRiepilogoDto(q.getId(), q.getTitolo(), q.getDescrizione(),
						q.getPubblicatoIl(), giaCompilati.contains(q.getId())))
				.toList();
	}

	// il questionario completo da compilare. Solo se e' PUBBLICATO:
	// le bozze per gli utenti non esistono (404), quelli chiusi non si compilano piu (409).
	// Anche un questionario destinato ad altre categorie di utenti per lui "non esiste" (404).
	@Transactional(readOnly = true)
	public QuestionarioDettaglioDto dettaglio(Long id, Utente utente) {
		Questionario questionario = questionarioRepository.findById(id)
				.filter(q -> q.getStato() != StatoQuestionario.BOZZA)
				.filter(q -> q.isDestinatario(utente))
				.orElseThrow(() -> new ApiException(HttpStatus.NOT_FOUND, "Questionario non trovato"));

		if (questionario.getStato() == StatoQuestionario.CHIUSO) {
			throw new ApiException(HttpStatus.CONFLICT, "Il questionario è chiuso e non accetta nuove compilazioni");
		}

		return new QuestionarioDettaglioDto(questionario.getId(), questionario.getTitolo(),
				questionario.getDescrizione(), domandeConOpzioni(questionario.getId()));
	}

	// carico le domande e, con UNA sola query in piu, tutte le loro opzioni
	private List<DomandaDto> domandeConOpzioni(Long questionarioId) {
		List<Domanda> domande = domandaRepository.findByQuestionarioIdOrderByOrdineAscIdAsc(questionarioId);
		if (domande.isEmpty()) {
			return List.of();
		}

		List<Long> idDomande = domande.stream().map(Domanda::getId).toList();
		Map<Long, List<Opzione>> opzioniPerDomanda = opzioneRepository
				.findByDomandaIdInOrderByOrdineAscIdAsc(idDomande).stream()
				.collect(Collectors.groupingBy(Opzione::getDomandaId));

		return domande.stream()
				.map(d -> new DomandaDto(d.getId(), d.getTesto(), d.getTipologia(), d.isObbligatoria(),
						opzioniPerDomanda.getOrDefault(d.getId(), List.of()).stream()
								.map(o -> new OpzioneDto(o.getId(), o.getTesto()))
								.toList()))
				.toList();
	}
}
