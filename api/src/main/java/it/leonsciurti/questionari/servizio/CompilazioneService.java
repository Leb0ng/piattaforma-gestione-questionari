package it.leonsciurti.questionari.servizio;

import java.time.LocalDateTime;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.HashSet;
import java.util.List;
import java.util.Map;
import java.util.Set;
import java.util.stream.Collectors;

import org.springframework.dao.DataIntegrityViolationException;
import org.springframework.http.HttpStatus;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import it.leonsciurti.questionari.dto.CompilazioneDettaglioDto;
import it.leonsciurti.questionari.dto.CompilazioneRiepilogoDto;
import it.leonsciurti.questionari.dto.InvioCompilazioneRequest;
import it.leonsciurti.questionari.dto.OpzioneSceltaDto;
import it.leonsciurti.questionari.dto.RispostaDataDto;
import it.leonsciurti.questionari.dto.RispostaRequest;
import it.leonsciurti.questionari.errori.ApiException;
import it.leonsciurti.questionari.modello.Compilazione;
import it.leonsciurti.questionari.modello.Domanda;
import it.leonsciurti.questionari.modello.Opzione;
import it.leonsciurti.questionari.modello.Questionario;
import it.leonsciurti.questionari.modello.Risposta;
import it.leonsciurti.questionari.modello.StatoQuestionario;
import it.leonsciurti.questionari.modello.TipologiaDomanda;
import it.leonsciurti.questionari.modello.Utente;
import it.leonsciurti.questionari.repository.CompilazioneRepository;
import it.leonsciurti.questionari.repository.DomandaRepository;
import it.leonsciurti.questionari.repository.OpzioneRepository;
import it.leonsciurti.questionari.repository.QuestionarioRepository;
import it.leonsciurti.questionari.repository.RispostaRepository;

// Il cuore del progetto: invio di una compilazione (con TUTTI i controlli lato server)
// e consultazione dello storico personale.
@Service
public class CompilazioneService {

	private static final int LUNGHEZZA_MASSIMA_TESTO = 5000;

	private final QuestionarioRepository questionarioRepository;
	private final DomandaRepository domandaRepository;
	private final OpzioneRepository opzioneRepository;
	private final CompilazioneRepository compilazioneRepository;
	private final RispostaRepository rispostaRepository;

	public CompilazioneService(QuestionarioRepository questionarioRepository, DomandaRepository domandaRepository,
			OpzioneRepository opzioneRepository, CompilazioneRepository compilazioneRepository,
			RispostaRepository rispostaRepository) {
		this.questionarioRepository = questionarioRepository;
		this.domandaRepository = domandaRepository;
		this.opzioneRepository = opzioneRepository;
		this.compilazioneRepository = compilazioneRepository;
		this.rispostaRepository = rispostaRepository;
	}

	// ------------------------------------------------------------------
	// INVIO
	// @Transactional = o si salva tutto (compilazione + risposte) o niente:
	// se un controllo fallisce a meta, nel db non resta nessun pezzo.
	// ------------------------------------------------------------------
	@Transactional
	public CompilazioneRiepilogoDto invia(Utente utente, Long questionarioId, InvioCompilazioneRequest richiesta) {

		// 1) il questionario deve esistere (le bozze per l'utente non esistono)
		//    e l'utente deve rientrare tra i suoi destinatari (sesso, eta, area, occupazione)
		Questionario questionario = questionarioRepository.findById(questionarioId)
				.filter(q -> q.getStato() != StatoQuestionario.BOZZA)
				.filter(q -> q.isDestinatario(utente))
				.orElseThrow(() -> new ApiException(HttpStatus.NOT_FOUND, "Questionario non trovato"));

		// 2) deve essere PUBBLICATO: se e' chiuso non accetta piu niente
		if (questionario.getStato() != StatoQuestionario.PUBBLICATO) {
			throw new ApiException(HttpStatus.CONFLICT, "Il questionario è chiuso e non accetta nuove compilazioni");
		}

		// 3) una sola compilazione per utente, da qualunque canale
		if (compilazioneRepository.existsByQuestionarioIdAndUtenteId(questionarioId, utente.getId())) {
			throw new ApiException(HttpStatus.CONFLICT, "Hai già compilato questo questionario");
		}

		// 4) controllo le risposte una per una e preparo le righe da salvare
		List<Risposta> righe = validaRisposte(questionarioId, richiesta.risposte());

		// 5) salvo la "busta". saveAndFlush manda subito l'INSERT al db: se nel frattempo
		//    e' arrivato un altro invio dello stesso utente (es. web e app nello stesso istante)
		//    scatta il vincolo UNIQUE del database e lo trasformo in un errore chiaro
		Compilazione compilazione;
		try {
			compilazione = compilazioneRepository.saveAndFlush(new Compilazione(questionarioId, utente.getId(),
					richiesta.canale(), LocalDateTime.now()));
		} catch (DataIntegrityViolationException e) {
			throw new ApiException(HttpStatus.CONFLICT, "Hai già compilato questo questionario");
		}

		// 6) salvo le risposte collegandole alla busta appena creata
		Long compilazioneId = compilazione.getId();
		rispostaRepository.saveAll(righe.stream()
				.map(r -> new Risposta(compilazioneId, r.getDomandaId(), r.getOpzioneId(), r.getTesto()))
				.toList());

		return new CompilazioneRiepilogoDto(compilazione.getId(), questionario.getId(), questionario.getTitolo(),
				questionario.getStato(), compilazione.getCanale(), compilazione.getInviataIl());
	}

	// Controlla obbligatorieta e validita delle risposte rispetto alle domande VERE lette dal db.
	// Se qualcosa non torna lancia un errore 400, altrimenti restituisce le righe da salvare
	// (senza compilazioneId, che ancora non esiste).
	private List<Risposta> validaRisposte(Long questionarioId, List<RispostaRequest> risposte) {
		List<Domanda> domande = domandaRepository.findByQuestionarioIdOrderByOrdineAscIdAsc(questionarioId);
		Set<Long> idDomande = domande.stream().map(Domanda::getId).collect(Collectors.toSet());

		// per ogni domanda, quali opzioni sono davvero sue
		Map<Long, Set<Long>> opzioniValide = new HashMap<>();
		if (!idDomande.isEmpty()) {
			for (Opzione opzione : opzioneRepository.findByDomandaIdInOrderByOrdineAscIdAsc(idDomande)) {
				opzioniValide.computeIfAbsent(opzione.getDomandaId(), id -> new HashSet<>()).add(opzione.getId());
			}
		}

		// metto le risposte arrivate in una mappa domandaId -> risposta
		Map<Long, RispostaRequest> rispostePerDomanda = new HashMap<>();
		for (RispostaRequest risposta : risposte) {
			if (risposta == null || !idDomande.contains(risposta.domandaId())) {
				throw errore("Una delle risposte si riferisce a una domanda che non fa parte di questo questionario");
			}
			if (rispostePerDomanda.put(risposta.domandaId(), risposta) != null) {
				throw errore("La stessa domanda compare due volte nelle risposte");
			}
		}

		// ora scorro le DOMANDE (non le risposte): cosi mi accorgo anche di quelle saltate
		List<Risposta> righe = new ArrayList<>();
		for (Domanda domanda : domande) {
			RispostaRequest risposta = rispostePerDomanda.get(domanda.getId());

			String testo = (risposta == null || risposta.testo() == null) ? "" : risposta.testo().trim();
			List<Long> scelte = (risposta == null || risposta.opzioniIds() == null) ? List.of()
					: risposta.opzioniIds();

			if (domanda.getTipologia() == TipologiaDomanda.APERTA) {
				if (!scelte.isEmpty()) {
					throw errore("La domanda \"" + domanda.getTesto() + "\" è a risposta aperta e non prevede opzioni");
				}
				if (testo.length() > LUNGHEZZA_MASSIMA_TESTO) {
					throw errore("La risposta a \"" + domanda.getTesto() + "\" supera i " + LUNGHEZZA_MASSIMA_TESTO
							+ " caratteri");
				}
				if (testo.isEmpty()) {
					if (domanda.isObbligatoria()) {
						throw errore("La domanda \"" + domanda.getTesto() + "\" è obbligatoria");
					}
					// facoltativa lasciata vuota: non salvo nessuna riga
				} else {
					righe.add(new Risposta(null, domanda.getId(), null, testo));
				}
			} else {
				// SINGOLA oppure MULTIPLA
				if (!testo.isEmpty()) {
					throw errore("La domanda \"" + domanda.getTesto() + "\" è a scelta e non prevede testo libero");
				}
				if (new HashSet<>(scelte).size() != scelte.size()) {
					throw errore("Nella domanda \"" + domanda.getTesto() + "\" la stessa opzione è indicata due volte");
				}
				if (!opzioniValide.getOrDefault(domanda.getId(), Set.of()).containsAll(scelte)) {
					throw errore("Nella domanda \"" + domanda.getTesto() + "\" c'è un'opzione che non le appartiene");
				}
				if (domanda.getTipologia() == TipologiaDomanda.SINGOLA && scelte.size() > 1) {
					throw errore("La domanda \"" + domanda.getTesto() + "\" accetta una sola opzione");
				}
				if (scelte.isEmpty() && domanda.isObbligatoria()) {
					throw errore("La domanda \"" + domanda.getTesto() + "\" è obbligatoria");
				}
				for (Long opzioneId : scelte) {
					righe.add(new Risposta(null, domanda.getId(), opzioneId, null));
				}
			}
		}
		return righe;
	}

	private ApiException errore(String messaggio) {
		return new ApiException(HttpStatus.BAD_REQUEST, messaggio);
	}

	// ------------------------------------------------------------------
	// STORICO: elenco delle MIE compilazioni
	// ------------------------------------------------------------------
	@Transactional(readOnly = true)
	public List<CompilazioneRiepilogoDto> elenco(Utente utente) {
		List<Compilazione> compilazioni = compilazioneRepository.findByUtenteIdOrderByInviataIlDesc(utente.getId());

		// carico in un colpo solo i questionari a cui si riferiscono
		Map<Long, Questionario> questionari = questionarioRepository
				.findAllById(compilazioni.stream().map(Compilazione::getQuestionarioId).toList()).stream()
				.collect(Collectors.toMap(Questionario::getId, q -> q));

		return compilazioni.stream()
				.map(c -> {
					Questionario q = questionari.get(c.getQuestionarioId());
					return new CompilazioneRiepilogoDto(c.getId(), q.getId(), q.getTitolo(), q.getStato(),
							c.getCanale(), c.getInviataIl());
				})
				.toList();
	}

	// ------------------------------------------------------------------
	// STORICO: una compilazione con le risposte date, in sola lettura.
	// La cerco per id E per utente: se l'id e' di un altro utente il risultato
	// e' "non trovata", esattamente come se non esistesse.
	// Funziona anche se nel frattempo il questionario e' stato chiuso.
	// ------------------------------------------------------------------
	@Transactional(readOnly = true)
	public CompilazioneDettaglioDto dettaglio(Utente utente, Long compilazioneId) {
		Compilazione compilazione = compilazioneRepository.findByIdAndUtenteId(compilazioneId, utente.getId())
				.orElseThrow(() -> new ApiException(HttpStatus.NOT_FOUND, "Compilazione non trovata"));

		Questionario questionario = questionarioRepository.findById(compilazione.getQuestionarioId())
				.orElseThrow(() -> new ApiException(HttpStatus.NOT_FOUND, "Questionario non trovato"));

		List<Domanda> domande = domandaRepository
				.findByQuestionarioIdOrderByOrdineAscIdAsc(questionario.getId());

		Map<Long, List<Opzione>> opzioniPerDomanda = domande.isEmpty() ? Map.of()
				: opzioneRepository.findByDomandaIdInOrderByOrdineAscIdAsc(
						domande.stream().map(Domanda::getId).toList()).stream()
						.collect(Collectors.groupingBy(Opzione::getDomandaId));

		// le righe salvate, raggruppate per domanda
		Map<Long, List<Risposta>> rispostePerDomanda = rispostaRepository.findByCompilazioneId(compilazione.getId())
				.stream()
				.collect(Collectors.groupingBy(Risposta::getDomandaId));

		List<RispostaDataDto> risposte = new ArrayList<>();
		for (Domanda domanda : domande) {
			List<Risposta> date = rispostePerDomanda.getOrDefault(domanda.getId(), List.of());

			// per le APERTE prendo il testo, per le altre l'elenco degli id scelti
			String testo = date.stream().map(Risposta::getTesto).filter(t -> t != null).findFirst().orElse(null);
			Set<Long> scelte = date.stream().map(Risposta::getOpzioneId).filter(id -> id != null)
					.collect(Collectors.toSet());

			List<OpzioneSceltaDto> opzioni = opzioniPerDomanda.getOrDefault(domanda.getId(), List.of()).stream()
					.map(o -> new OpzioneSceltaDto(o.getId(), o.getTesto(), scelte.contains(o.getId())))
					.toList();

			risposte.add(new RispostaDataDto(domanda.getId(), domanda.getTesto(), domanda.getTipologia(),
					domanda.isObbligatoria(), testo, opzioni));
		}

		return new CompilazioneDettaglioDto(compilazione.getId(), questionario.getId(), questionario.getTitolo(),
				questionario.getDescrizione(), questionario.getStato(), compilazione.getCanale(),
				compilazione.getInviataIl(), risposte);
	}
}
