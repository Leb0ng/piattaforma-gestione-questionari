<?php
declare(strict_types=1);

final class QuestionariController
{
    private const TITOLO_MAX = 200;
    private const DESCRIZIONE_MAX = 5000;

    public function elenco(): void
    {
        $questionari = QuestionarioModel::tutti();

        // numeri per la fascia in alto
        $cifre = ['BOZZA' => 0, 'PUBBLICATO' => 0, 'CHIUSO' => 0, 'compilazioni' => 0];
        foreach ($questionari as $q) {
            $cifre[$q['stato']]++;
            $cifre['compilazioni'] += (int) $q['num_compilazioni'];
        }

        vista('questionari/elenco', ['questionari' => $questionari, 'cifre' => $cifre], 'Questionari');
    }

    public function dettaglio(): void
    {
        $questionario = $this->trovaOppure404(idDa($_GET, 'id'));
        $id = (int) $questionario['id'];
        $domande = DomandaModel::perQuestionario($id);
        $inBozza = $questionario['stato'] === 'BOZZA';

        // le statistiche hanno senso solo dopo la pubblicazione e se c'e' almeno una risposta
        $statistiche = null;
        if (!$inBozza && (int) $questionario['num_compilazioni'] > 0) {
            $statistiche = [
                'riepilogo' => StatisticheModel::riepilogo($id),
                'domande'   => StatisticheModel::perDomanda($id, $domande),
            ];
        }

        vista('questionari/dettaglio', [
            'questionario' => $questionario,
            'domande'      => $domande,
            'problemi'     => $inBozza ? QuestionarioModel::problemiPubblicazione($id) : [],
            'statistiche'  => $statistiche,
        ], $questionario['titolo']);
    }

    public function nuovo(): void
    {
        // destinatari: nessun filtro = il questionario e' per tutti
        $dati = ['titolo' => '', 'descrizione' => '', 'destinatari' => array_fill_keys(array_keys(categorie()), [])];
        $errori = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            richiediPost();
            [$dati, $errori] = $this->leggiForm();
            if (!$errori) {
                $id = QuestionarioModel::crea($dati['titolo'], $dati['descrizione'], $dati['destinatari'], richiediAdmin()['id']);
                flash('ok', 'Bozza creata. Ora aggiungi le domande.');
                vai('questionario', ['id' => $id]);
            }
        }

        vista('questionari/form', ['dati' => $dati, 'errori' => $errori, 'questionario' => null], 'Nuovo questionario');
    }

    public function modifica(): void
    {
        $questionario = $this->trovaOppure404(idDa($_GET, 'id'));
        $id = (int) $questionario['id'];

        if ($questionario['stato'] !== 'BOZZA') {
            flash('errore', 'Solo i questionari in bozza si possono modificare.');
            vai('questionario', ['id' => $id]);
        }

        $dati = [
            'titolo'      => $questionario['titolo'],
            'descrizione' => $questionario['descrizione'],
            'destinatari' => destinatariDi($questionario),
        ];
        $errori = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            richiediPost();
            [$dati, $errori] = $this->leggiForm();
            if (!$errori) {
                // il modello ricontrolla lo stato dentro la query: se nel frattempo
                // il questionario e' stato pubblicato, l'UPDATE non tocca nessuna riga
                if (QuestionarioModel::aggiorna($id, $dati['titolo'], $dati['descrizione'], $dati['destinatari'])) {
                    flash('ok', 'Modifiche salvate.');
                } else {
                    flash('errore', 'Il questionario non è più in bozza: le modifiche non sono state salvate.');
                }
                vai('questionario', ['id' => $id]);
            }
        }

        vista('questionari/form', ['dati' => $dati, 'errori' => $errori, 'questionario' => $questionario], 'Modifica questionario');
    }

    public function elimina(): void
    {
        richiediPost();
        $id = idDa($_POST, 'id');

        if (QuestionarioModel::elimina($id)) {
            flash('ok', 'Bozza eliminata.');
            vai('questionari');
        }
        flash('errore', 'Si possono eliminare solo i questionari in bozza.');
        vai('questionario', ['id' => $id]);
    }

    public function pubblica(): void
    {
        richiediPost();
        $id = idDa($_POST, 'id');
        $questionario = $this->trovaOppure404($id);

        if ($questionario['stato'] !== 'BOZZA') {
            flash('errore', 'Questo questionario è già stato pubblicato.');
        } elseif ($problemi = QuestionarioModel::problemiPubblicazione($id)) {
            flash('errore', 'Non si può ancora pubblicare. ' . implode(' ', $problemi));
        } elseif (QuestionarioModel::pubblica($id)) {
            flash('ok', 'Pubblicato. Da adesso gli utenti possono compilarlo.');
        } else {
            flash('errore', 'Pubblicazione non riuscita: il questionario non è più in bozza.');
        }
        vai('questionario', ['id' => $id]);
    }

    public function chiudi(): void
    {
        richiediPost();
        $id = idDa($_POST, 'id');
        $this->trovaOppure404($id);

        if (QuestionarioModel::chiudi($id)) {
            flash('ok', 'Chiuso. Non accetta più compilazioni; le risposte ricevute restano consultabili.');
        } else {
            flash('errore', 'Si possono chiudere solo i questionari pubblicati.');
        }
        vai('questionario', ['id' => $id]);
    }

    // ---------- di servizio ----------

    private function trovaOppure404(int $id): array
    {
        $questionario = $id > 0 ? QuestionarioModel::trova($id) : null;
        if ($questionario === null) {
            nonTrovato('Questo questionario non esiste o è stato eliminato.');
        }
        return $questionario;
    }

    // Legge e controlla titolo, descrizione e destinatari. Restituisce [dati, errori].
    private function leggiForm(): array
    {
        $dati = [
            'titolo'      => testoDa($_POST, 'titolo'),
            'descrizione' => testoDa($_POST, 'descrizione'),
            'destinatari' => [],
        ];

        // destinatari: arrivano come dest[sesso][]=DONNA, dest[eta][]=UNDER_30 ...
        // Tengo solo i valori previsti (un valore inventato viene ignorato) e li
        // rimetto nell'ordine di categorie(), cosi nel db sono sempre scritti uguali.
        $inviati = is_array($_POST['dest'] ?? null) ? $_POST['dest'] : [];
        foreach (categorie() as $chiave => $categoria) {
            $scelti = is_array($inviati[$chiave] ?? null) ? $inviati[$chiave] : [];
            $dati['destinatari'][$chiave] = array_values(array_filter(
                array_keys($categoria['valori']),
                fn($valore) => in_array($valore, $scelti, true)
            ));
        }
        $errori = [];

        if ($dati['titolo'] === '') {
            $errori['titolo'] = 'Scrivi un titolo.';
        } elseif (mb_strlen($dati['titolo']) > self::TITOLO_MAX) {
            $errori['titolo'] = 'Il titolo può avere al massimo ' . self::TITOLO_MAX . ' caratteri.';
        }

        if ($dati['descrizione'] === '') {
            $errori['descrizione'] = 'Scrivi una descrizione: è quella che gli utenti leggono prima di compilare.';
        } elseif (mb_strlen($dati['descrizione']) > self::DESCRIZIONE_MAX) {
            $errori['descrizione'] = 'La descrizione può avere al massimo ' . self::DESCRIZIONE_MAX . ' caratteri.';
        }

        return [$dati, $errori];
    }
}
