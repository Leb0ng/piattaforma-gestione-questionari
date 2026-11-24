<?php
declare(strict_types=1);

// Query della dashboard: solo letture, niente modifiche.
// I questionari li prendo da QuestionarioModel::tutti() (ha gia destinatari e
// compilazioni contati), qui ci sono solo i numeri che non stanno da nessun'altra parte.
final class DashboardModel
{
    // numeri generali su utenti e compilazioni, in una query sola
    public static function riepilogo(): array
    {
        $riga = Database::connessione()->query(
            "SELECT
                (SELECT COUNT(*) FROM utenti WHERE ruolo = 'UTENTE') AS utenti,
                (SELECT COUNT(*) FROM utenti WHERE ruolo = 'UTENTE'
                    AND (sesso IS NULL OR data_nascita IS NULL OR area_geografica IS NULL OR occupazione IS NULL)
                ) AS profili_incompleti,
                (SELECT COUNT(*) FROM compilazioni) AS compilazioni,
                (SELECT COUNT(*) FROM compilazioni WHERE inviata_il >= CURDATE() - INTERVAL 6 DAY) AS compilazioni_7gg,
                (SELECT COUNT(*) FROM compilazioni WHERE canale = 'WEB') AS canale_web,
                (SELECT COUNT(*) FROM compilazioni WHERE canale = 'MOBILE') AS canale_mobile"
        )->fetch();

        return array_map('intval', $riga);
    }

    // compilazioni per giorno negli ultimi 14 giorni, oggi compreso.
    // MySQL restituisce solo i giorni che hanno almeno una compilazione:
    // i giorni vuoti li riempio io con 0, cosi il grafico ha sempre 14 barre.
    public static function andamento(int $giorni = 14): array
    {
        $query = Database::connessione()->prepare(
            'SELECT DATE(inviata_il) AS giorno, COUNT(*) AS totale
               FROM compilazioni
              WHERE inviata_il >= CURDATE() - INTERVAL ? DAY
              GROUP BY DATE(inviata_il)'
        );
        $query->execute([$giorni - 1]);
        $perGiorno = array_column($query->fetchAll(), 'totale', 'giorno');

        $serie = [];
        $oggi = new DateTimeImmutable('today');
        for ($i = $giorni - 1; $i >= 0; $i--) {
            $giorno = $oggi->modify("-{$i} days")->format('Y-m-d');
            $serie[$giorno] = (int) ($perGiorno[$giorno] ?? 0);
        }
        return $serie;
    }

    // le ultime compilazioni arrivate, da qualunque questionario
    public static function ultimeCompilazioni(int $quante = 6): array
    {
        $query = Database::connessione()->prepare(
            'SELECT c.id, c.canale, c.inviata_il, u.nome, u.username, q.id AS questionario_id, q.titolo
               FROM compilazioni c
               JOIN utenti u ON u.id = c.utente_id
               JOIN questionari q ON q.id = c.questionario_id
              ORDER BY c.inviata_il DESC, c.id DESC
              LIMIT ?'
        );
        $query->execute([$quante]);
        return $query->fetchAll();
    }

    // utenti a cui manca almeno un dato del profilo: non rientrano nei questionari
    // che filtrano proprio su quel dato, quindi e' utile saperlo
    public static function profiliIncompleti(int $quanti = 5): array
    {
        $query = Database::connessione()->prepare(
            "SELECT id, nome, username, sesso, data_nascita, area_geografica, occupazione
               FROM utenti
              WHERE ruolo = 'UTENTE'
                AND (sesso IS NULL OR data_nascita IS NULL OR area_geografica IS NULL OR occupazione IS NULL)
              ORDER BY creato_il DESC, id DESC
              LIMIT ?"
        );
        $query->execute([$quanti]);
        return $query->fetchAll();
    }
}
