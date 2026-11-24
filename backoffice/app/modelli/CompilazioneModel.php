<?php
declare(strict_types=1);

// Le compilazioni il Back Office le LEGGE soltanto: a scriverle sono le API.
final class CompilazioneModel
{
    public static function perQuestionario(int $questionarioId): array
    {
        $query = Database::connessione()->prepare(
            'SELECT c.id, c.canale, c.inviata_il, u.nome, u.username
               FROM compilazioni c JOIN utenti u ON u.id = c.utente_id
              WHERE c.questionario_id = ?
              ORDER BY c.inviata_il DESC, c.id DESC'
        );
        $query->execute([$questionarioId]);
        return $query->fetchAll();
    }

    public static function trova(int $id): ?array
    {
        $query = Database::connessione()->prepare(
            'SELECT c.id, c.questionario_id, c.canale, c.inviata_il, u.nome, u.username
               FROM compilazioni c JOIN utenti u ON u.id = c.utente_id
              WHERE c.id = ?'
        );
        $query->execute([$id]);
        return $query->fetch() ?: null;
    }

    // Le risposte di una compilazione, raggruppate per domanda:
    //   [domanda_id => ['testo' => '...' oppure null, 'opzioni' => [id opzione scelta, ...]]]
    public static function risposte(int $compilazioneId): array
    {
        $query = Database::connessione()->prepare(
            'SELECT domanda_id, opzione_id, testo FROM risposte WHERE compilazione_id = ?'
        );
        $query->execute([$compilazioneId]);

        $risposte = [];
        foreach ($query->fetchAll() as $riga) {
            $domandaId = (int) $riga['domanda_id'];
            $risposte[$domandaId] ??= ['testo' => null, 'opzioni' => []];
            if ($riga['opzione_id'] !== null) {
                $risposte[$domandaId]['opzioni'][] = (int) $riga['opzione_id'];
            } else {
                $risposte[$domandaId]['testo'] = $riga['testo'];
            }
        }
        return $risposte;
    }
}
