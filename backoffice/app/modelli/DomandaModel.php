<?php
declare(strict_types=1);

// Query su "domande" e "opzioni".
// Ogni modifica avviene dentro una transazione che per prima cosa BLOCCA la riga
// del questionario (SELECT ... FOR UPDATE) e controlla che sia ancora in BOZZA:
// se nel frattempo e' stato pubblicato, la modifica non parte.
final class DomandaModel
{
    // le domande di un questionario, ognuna con dentro le sue opzioni
    public static function perQuestionario(int $questionarioId): array
    {
        $db = Database::connessione();

        $query = $db->prepare(
            'SELECT id, questionario_id, testo, tipologia, obbligatoria, ordine
               FROM domande WHERE questionario_id = ? ORDER BY ordine, id'
        );
        $query->execute([$questionarioId]);
        $domande = [];
        foreach ($query->fetchAll() as $riga) {
            $riga['opzioni'] = [];
            $domande[(int) $riga['id']] = $riga;
        }

        // una sola query in piu per tutte le opzioni, poi le distribuisco
        $query = $db->prepare(
            'SELECT o.id, o.domanda_id, o.testo
               FROM opzioni o JOIN domande d ON d.id = o.domanda_id
              WHERE d.questionario_id = ? ORDER BY o.ordine, o.id'
        );
        $query->execute([$questionarioId]);
        foreach ($query->fetchAll() as $opzione) {
            $domande[(int) $opzione['domanda_id']]['opzioni'][] = $opzione;
        }

        return array_values($domande);
    }

    public static function trova(int $id): ?array
    {
        $db = Database::connessione();
        $query = $db->prepare(
            'SELECT d.id, d.questionario_id, d.testo, d.tipologia, d.obbligatoria, d.ordine, q.stato, q.titolo AS questionario
               FROM domande d JOIN questionari q ON q.id = d.questionario_id
              WHERE d.id = ?'
        );
        $query->execute([$id]);
        $domanda = $query->fetch();
        if (!$domanda) {
            return null;
        }

        $query = $db->prepare('SELECT id, testo FROM opzioni WHERE domanda_id = ? ORDER BY ordine, id');
        $query->execute([$id]);
        $domanda['opzioni'] = $query->fetchAll();
        return $domanda;
    }

    public static function crea(int $questionarioId, string $testo, string $tipologia, bool $obbligatoria, array $opzioni): bool
    {
        $db = Database::connessione();
        $db->beginTransaction();
        try {
            if (!self::bloccaSeBozza($db, $questionarioId)) {
                $db->rollBack();
                return false;
            }

            // la nuova domanda va in fondo
            $query = $db->prepare('SELECT COALESCE(MAX(ordine), 0) + 1 FROM domande WHERE questionario_id = ?');
            $query->execute([$questionarioId]);
            $ordine = (int) $query->fetchColumn();

            $db->prepare('INSERT INTO domande (questionario_id, testo, tipologia, obbligatoria, ordine) VALUES (?, ?, ?, ?, ?)')
               ->execute([$questionarioId, $testo, $tipologia, (int) $obbligatoria, $ordine]);

            self::salvaOpzioni($db, (int) $db->lastInsertId(), $opzioni);

            $db->commit();
            return true;
        } catch (Throwable $errore) {
            $db->rollBack();
            throw $errore;
        }
    }

    public static function aggiorna(int $id, string $testo, string $tipologia, bool $obbligatoria, array $opzioni): bool
    {
        $db = Database::connessione();
        $db->beginTransaction();
        try {
            $query = $db->prepare('SELECT questionario_id FROM domande WHERE id = ?');
            $query->execute([$id]);
            $questionarioId = (int) $query->fetchColumn();

            if ($questionarioId === 0 || !self::bloccaSeBozza($db, $questionarioId)) {
                $db->rollBack();
                return false;
            }

            $db->prepare('UPDATE domande SET testo = ?, tipologia = ?, obbligatoria = ? WHERE id = ?')
               ->execute([$testo, $tipologia, (int) $obbligatoria, $id]);

            // le opzioni le riscrivo da capo: in bozza non esistono ancora risposte collegate
            $db->prepare('DELETE FROM opzioni WHERE domanda_id = ?')->execute([$id]);
            self::salvaOpzioni($db, $id, $opzioni);

            $db->commit();
            return true;
        } catch (Throwable $errore) {
            $db->rollBack();
            throw $errore;
        }
    }

    public static function elimina(int $id): bool
    {
        // DELETE con JOIN: cancella la domanda solo se il suo questionario e' in BOZZA
        $query = Database::connessione()->prepare(
            "DELETE d FROM domande d JOIN questionari q ON q.id = d.questionario_id
              WHERE d.id = ? AND q.stato = 'BOZZA'"
        );
        $query->execute([$id]);
        return $query->rowCount() === 1;
    }

    // Sposta la domanda di un posto in su o in giu, rinumerando l'ordine di tutte
    public static function sposta(int $id, string $direzione): bool
    {
        $db = Database::connessione();
        $db->beginTransaction();
        try {
            $query = $db->prepare('SELECT questionario_id FROM domande WHERE id = ?');
            $query->execute([$id]);
            $questionarioId = (int) $query->fetchColumn();

            if ($questionarioId === 0 || !self::bloccaSeBozza($db, $questionarioId)) {
                $db->rollBack();
                return false;
            }

            $query = $db->prepare('SELECT id FROM domande WHERE questionario_id = ? ORDER BY ordine, id');
            $query->execute([$questionarioId]);
            $ids = array_map('intval', $query->fetchAll(PDO::FETCH_COLUMN));

            $posizione = array_search($id, $ids, true);
            $nuova = $direzione === 'su' ? $posizione - 1 : $posizione + 1;

            if ($posizione !== false && $nuova >= 0 && $nuova < count($ids)) {
                // scambio i due elementi nell'elenco e riscrivo l'ordine 1, 2, 3...
                [$ids[$posizione], $ids[$nuova]] = [$ids[$nuova], $ids[$posizione]];
                $aggiorna = $db->prepare('UPDATE domande SET ordine = ? WHERE id = ?');
                foreach ($ids as $indice => $domandaId) {
                    $aggiorna->execute([$indice + 1, $domandaId]);
                }
            }

            $db->commit();
            return true;
        } catch (Throwable $errore) {
            $db->rollBack();
            throw $errore;
        }
    }

    // Blocca la riga del questionario fino alla fine della transazione e dice se e' in BOZZA
    private static function bloccaSeBozza(PDO $db, int $questionarioId): bool
    {
        $query = $db->prepare('SELECT stato FROM questionari WHERE id = ? FOR UPDATE');
        $query->execute([$questionarioId]);
        return $query->fetchColumn() === 'BOZZA';
    }

    private static function salvaOpzioni(PDO $db, int $domandaId, array $opzioni): void
    {
        $query = $db->prepare('INSERT INTO opzioni (domanda_id, testo, ordine) VALUES (?, ?, ?)');
        foreach (array_values($opzioni) as $indice => $testo) {
            $query->execute([$domandaId, $testo, $indice + 1]);
        }
    }
}
