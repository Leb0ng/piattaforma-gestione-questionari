<?php
declare(strict_types=1);

final class DomandeController
{
    private const TESTO_MAX = 500;
    private const OPZIONE_MAX = 255;
    private const TIPOLOGIE = ['APERTA', 'SINGOLA', 'MULTIPLA'];

    public function nuova(): void
    {
        $questionario = $this->questionarioInBozza(idDa($_GET, 'questionario'));
        $questionarioId = (int) $questionario['id'];

        $dati = ['testo' => '', 'tipologia' => 'APERTA', 'obbligatoria' => false, 'opzioni' => []];
        $errori = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            richiediPost();
            [$dati, $errori] = $this->leggiForm();
            if (!$errori) {
                if (DomandaModel::crea($questionarioId, $dati['testo'], $dati['tipologia'], $dati['obbligatoria'], $dati['opzioni'])) {
                    flash('ok', 'Domanda aggiunta.');
                } else {
                    flash('errore', 'Il questionario non è più in bozza: la domanda non è stata aggiunta.');
                }
                vai('questionario', ['id' => $questionarioId]);
            }
        }

        vista('domande/form', [
            'questionario' => $questionario, 'domanda' => null, 'dati' => $dati, 'errori' => $errori,
        ], 'Nuova domanda');
    }

    public function modifica(): void
    {
        $domanda = DomandaModel::trova(idDa($_GET, 'id'));
        if ($domanda === null) {
            nonTrovato('Questa domanda non esiste o è stata eliminata.');
        }
        $questionario = $this->questionarioInBozza((int) $domanda['questionario_id']);
        $questionarioId = (int) $questionario['id'];

        $dati = [
            'testo'        => $domanda['testo'],
            'tipologia'    => $domanda['tipologia'],
            'obbligatoria' => (bool) $domanda['obbligatoria'],
            'opzioni'      => array_column($domanda['opzioni'], 'testo'),
        ];
        $errori = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            richiediPost();
            [$dati, $errori] = $this->leggiForm();
            if (!$errori) {
                if (DomandaModel::aggiorna((int) $domanda['id'], $dati['testo'], $dati['tipologia'], $dati['obbligatoria'], $dati['opzioni'])) {
                    flash('ok', 'Domanda aggiornata.');
                } else {
                    flash('errore', 'Il questionario non è più in bozza: la domanda non è stata modificata.');
                }
                vai('questionario', ['id' => $questionarioId]);
            }
        }

        vista('domande/form', [
            'questionario' => $questionario, 'domanda' => $domanda, 'dati' => $dati, 'errori' => $errori,
        ], 'Modifica domanda');
    }

    public function elimina(): void
    {
        richiediPost();
        $domanda = DomandaModel::trova(idDa($_POST, 'id'));
        if ($domanda === null) {
            nonTrovato('Questa domanda non esiste o è stata eliminata.');
        }

        if (DomandaModel::elimina((int) $domanda['id'])) {
            flash('ok', 'Domanda eliminata.');
        } else {
            flash('errore', 'Le domande si possono eliminare solo finché il questionario è in bozza.');
        }
        vai('questionario', ['id' => (int) $domanda['questionario_id']]);
    }

    public function sposta(): void
    {
        richiediPost();
        $domanda = DomandaModel::trova(idDa($_POST, 'id'));
        if ($domanda === null) {
            nonTrovato('Questa domanda non esiste o è stata eliminata.');
        }
        $direzione = testoDa($_POST, 'direzione') === 'su' ? 'su' : 'giu';

        if (!DomandaModel::sposta((int) $domanda['id'], $direzione)) {
            flash('errore', 'L\'ordine delle domande si può cambiare solo finché il questionario è in bozza.');
        }
        vai('questionario', ['id' => (int) $domanda['questionario_id']]);
    }

    // ---------- di servizio ----------

    // Le pagine delle domande esistono solo per i questionari in bozza
    private function questionarioInBozza(int $id): array
    {
        $questionario = $id > 0 ? QuestionarioModel::trova($id) : null;
        if ($questionario === null) {
            nonTrovato('Questo questionario non esiste o è stato eliminato.');
        }
        if ($questionario['stato'] !== 'BOZZA') {
            flash('errore', 'Le domande si possono modificare solo finché il questionario è in bozza.');
            vai('questionario', ['id' => (int) $questionario['id']]);
        }
        return $questionario;
    }

    // Legge e controlla i dati della domanda. Restituisce [dati, errori].
    private function leggiForm(): array
    {
        $tipologia = testoDa($_POST, 'tipologia');
        $grezze = is_array($_POST['opzioni'] ?? null) ? $_POST['opzioni'] : [];

        // tengo solo le opzioni scritte davvero (quelle lasciate vuote le scarto)
        $opzioni = [];
        foreach ($grezze as $opzione) {
            if (is_string($opzione) && trim($opzione) !== '') {
                $opzioni[] = trim($opzione);
            }
        }

        $dati = [
            'testo'        => testoDa($_POST, 'testo'),
            'tipologia'    => $tipologia,
            'obbligatoria' => isset($_POST['obbligatoria']),
            'opzioni'      => $opzioni,
        ];
        $errori = [];

        if ($dati['testo'] === '') {
            $errori['testo'] = 'Scrivi il testo della domanda.';
        } elseif (mb_strlen($dati['testo']) > self::TESTO_MAX) {
            $errori['testo'] = 'La domanda può avere al massimo ' . self::TESTO_MAX . ' caratteri.';
        }

        if (!in_array($tipologia, self::TIPOLOGIE, true)) {
            $errori['tipologia'] = 'Scegli una tipologia.';
            $dati['tipologia'] = 'APERTA';
        } elseif ($tipologia === 'APERTA') {
            // una domanda aperta non ha opzioni: se ne arrivano le ignoro
            $dati['opzioni'] = [];
        } else {
            // regola della traccia: le domande a scelta hanno almeno due opzioni
            if (count($opzioni) < 2) {
                $errori['opzioni'] = 'Una domanda a scelta ha bisogno di almeno due opzioni.';
            } elseif (count(array_unique(array_map('mb_strtolower', $opzioni))) !== count($opzioni)) {
                $errori['opzioni'] = 'Due opzioni hanno lo stesso testo.';
            } else {
                foreach ($opzioni as $opzione) {
                    if (mb_strlen($opzione) > self::OPZIONE_MAX) {
                        $errori['opzioni'] = 'Ogni opzione può avere al massimo ' . self::OPZIONE_MAX . ' caratteri.';
                        break;
                    }
                }
            }
        }

        return [$dati, $errori];
    }
}
