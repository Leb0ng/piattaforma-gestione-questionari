<?php
declare(strict_types=1);

// Tutte le query sulla tabella "questionari".
// REGOLA CHIAVE: le operazioni permesse solo in un certo stato hanno lo stato
// DENTRO il WHERE (es. "... AND stato = 'BOZZA'"). Cosi il controllo e la modifica
// sono un'unica operazione nel database: non esiste un istante in cui lo stato
// puo cambiare tra il "controllo" e il "salvo".
final class QuestionarioModel
{
    // num_destinatari = quanti utenti rientrano oggi nei destinatari.
    // E' la stessa regola delle API (Questionario.isDestinatario):
    //   filtro vuoto = tutti, altrimenti FIND_IN_SET cerca il valore dell'utente nell'elenco;
    //   un dato NULL non e' in nessun elenco, quindi l'utente resta fuori.
    // La fascia d'eta si calcola dalla data di nascita con gli stessi confini di fasciaEta().
    private const CAMPI = "q.id, q.titolo, q.descrizione, q.stato, q.creato_il, q.pubblicato_il, q.chiuso_il,
        q.dest_sesso, q.dest_eta, q.dest_area, q.dest_occupazione,
        (SELECT COUNT(*) FROM domande d WHERE d.questionario_id = q.id) AS num_domande,
        (SELECT COUNT(*) FROM compilazioni c WHERE c.questionario_id = q.id) AS num_compilazioni,
        (SELECT COUNT(*) FROM utenti u
          WHERE u.ruolo = 'UTENTE'
            AND (q.dest_sesso = '' OR FIND_IN_SET(u.sesso, q.dest_sesso))
            AND (q.dest_eta = '' OR FIND_IN_SET(
                  CASE WHEN u.data_nascita IS NULL THEN NULL
                       WHEN TIMESTAMPDIFF(YEAR, u.data_nascita, CURDATE()) < 30 THEN 'UNDER_30'
                       WHEN TIMESTAMPDIFF(YEAR, u.data_nascita, CURDATE()) <= 50 THEN 'DA_30_A_50'
                       ELSE 'OVER_50' END, q.dest_eta))
            AND (q.dest_area = '' OR FIND_IN_SET(u.area_geografica, q.dest_area))
            AND (q.dest_occupazione = '' OR FIND_IN_SET(u.occupazione, q.dest_occupazione))
        ) AS num_destinatari";

    public static function tutti(): array
    {
        return Database::connessione()
            ->query('SELECT ' . self::CAMPI . ' FROM questionari q ORDER BY q.creato_il DESC, q.id DESC')
            ->fetchAll();
    }

    public static function trova(int $id): ?array
    {
        $query = Database::connessione()->prepare('SELECT ' . self::CAMPI . ' FROM questionari q WHERE q.id = ?');
        $query->execute([$id]);
        return $query->fetch() ?: null;
    }

    // un questionario nuovo nasce sempre in BOZZA (e' il default della colonna).
    // $destinatari: ['sesso' => ['DONNA'], 'eta' => [], ...] gia controllati dal controller
    public static function crea(string $titolo, string $descrizione, array $destinatari, int $adminId): int
    {
        $db = Database::connessione();
        $db->prepare(
            'INSERT INTO questionari (titolo, descrizione, dest_sesso, dest_eta, dest_area, dest_occupazione, creato_da)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        )->execute([$titolo, $descrizione, ...self::valoriDestinatari($destinatari), $adminId]);
        return (int) $db->lastInsertId();
    }

    // anche i destinatari si possono cambiare solo in bozza: dopo la pubblicazione
    // sono bloccati come titolo e domande
    public static function aggiorna(int $id, string $titolo, string $descrizione, array $destinatari): bool
    {
        $query = Database::connessione()->prepare(
            "UPDATE questionari
                SET titolo = ?, descrizione = ?, dest_sesso = ?, dest_eta = ?, dest_area = ?, dest_occupazione = ?
              WHERE id = ? AND stato = 'BOZZA'"
        );
        $query->execute([$titolo, $descrizione, ...self::valoriDestinatari($destinatari), $id]);
        return $query->rowCount() === 1;
    }

    // le 4 liste diventano i testi per le colonne SET, nell'ordine di categorie(): "UNDER_30,OVER_50"
    private static function valoriDestinatari(array $destinatari): array
    {
        return array_map(fn($chiave) => implode(',', $destinatari[$chiave] ?? []), array_keys(categorie()));
    }

    // domande e opzioni spariscono da sole grazie a ON DELETE CASCADE
    public static function elimina(int $id): bool
    {
        $query = Database::connessione()->prepare("DELETE FROM questionari WHERE id = ? AND stato = 'BOZZA'");
        $query->execute([$id]);
        return $query->rowCount() === 1;
    }

    // Cosa manca per poter pubblicare? Lista vuota = si puo pubblicare.
    public static function problemiPubblicazione(int $id): array
    {
        $db = Database::connessione();
        $problemi = [];

        $query = $db->prepare('SELECT COUNT(*) FROM domande WHERE questionario_id = ?');
        $query->execute([$id]);
        if ((int) $query->fetchColumn() === 0) {
            $problemi[] = 'Aggiungi almeno una domanda.';
        }

        // domande a scelta con meno di due opzioni
        $query = $db->prepare(
            "SELECT d.testo
               FROM domande d
               LEFT JOIN opzioni o ON o.domanda_id = d.id
              WHERE d.questionario_id = ? AND d.tipologia IN ('SINGOLA', 'MULTIPLA')
              GROUP BY d.id, d.testo
             HAVING COUNT(o.id) < 2"
        );
        $query->execute([$id]);
        foreach ($query->fetchAll() as $riga) {
            $problemi[] = 'La domanda "' . $riga['testo'] . '" deve avere almeno due opzioni.';
        }

        return $problemi;
    }

    // BOZZA -> PUBBLICATO (mai il contrario: il WHERE accetta solo le bozze)
    public static function pubblica(int $id): bool
    {
        $query = Database::connessione()->prepare(
            "UPDATE questionari SET stato = 'PUBBLICATO', pubblicato_il = NOW() WHERE id = ? AND stato = 'BOZZA'"
        );
        $query->execute([$id]);
        return $query->rowCount() === 1;
    }

    // PUBBLICATO -> CHIUSO (un questionario chiuso non si riapre)
    public static function chiudi(int $id): bool
    {
        $query = Database::connessione()->prepare(
            "UPDATE questionari SET stato = 'CHIUSO', chiuso_il = NOW() WHERE id = ? AND stato = 'PUBBLICATO'"
        );
        $query->execute([$id]);
        return $query->rowCount() === 1;
    }
}
