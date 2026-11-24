<?php
declare(strict_types=1);

final class UtenteModel
{
    // i campi del profilo, nello stesso ordine usato da crea() e aggiorna()
    private const PROFILO = ['sesso', 'data_nascita', 'area_geografica', 'occupazione'];

    public static function trovaPerUsername(string $username): ?array
    {
        $query = Database::connessione()->prepare(
            'SELECT id, username, password_hash, nome, ruolo FROM utenti WHERE username = ?'
        );
        $query->execute([$username]);
        return $query->fetch() ?: null;
    }

    // tutti gli utenti (non l'admin) con il loro profilo e quante compilazioni hanno inviato
    public static function tutti(): array
    {
        return Database::connessione()->query(
            "SELECT u.id, u.username, u.nome, u.sesso, u.data_nascita, u.area_geografica, u.occupazione, u.creato_il,
                    (SELECT COUNT(*) FROM compilazioni c WHERE c.utente_id = u.id) AS num_compilazioni
               FROM utenti u
              WHERE u.ruolo = 'UTENTE'
              ORDER BY u.nome, u.username"
        )->fetchAll();
    }

    // solo gli utenti normali: l'admin non si gestisce da questa sezione
    public static function trova(int $id): ?array
    {
        $query = Database::connessione()->prepare(
            "SELECT id, username, nome, sesso, data_nascita, area_geografica, occupazione
               FROM utenti WHERE id = ? AND ruolo = 'UTENTE'"
        );
        $query->execute([$id]);
        return $query->fetch() ?: null;
    }

    public static function usernameEsiste(string $username): bool
    {
        $query = Database::connessione()->prepare('SELECT 1 FROM utenti WHERE username = ?');
        $query->execute([$username]);
        return (bool) $query->fetchColumn();
    }

    // $profilo: ['sesso' => 'DONNA' o null, 'data_nascita' => '1990-05-01' o null, ...]
    // Se nel frattempo qualcuno ha preso lo stesso username, l'UNIQUE del db
    // fa fallire l'INSERT: in quel caso restituisco 0.
    public static function crea(string $username, string $nome, string $passwordHash, array $profilo): int
    {
        $db = Database::connessione();
        try {
            $db->prepare(
                "INSERT INTO utenti (username, password_hash, nome, ruolo, sesso, data_nascita, area_geografica, occupazione)
                 VALUES (?, ?, ?, 'UTENTE', ?, ?, ?, ?)"
            )->execute([$username, $passwordHash, $nome, ...self::valoriProfilo($profilo)]);
        } catch (PDOException $errore) {
            if ($errore->getCode() === '23000') { // violazione di un vincolo: username doppio
                return 0;
            }
            throw $errore;
        }
        return (int) $db->lastInsertId();
    }

    // nome e profilo; la password solo se ne e' stata scritta una nuova ($passwordHash non null)
    public static function aggiorna(int $id, string $nome, array $profilo, ?string $passwordHash): bool
    {
        $sql = 'UPDATE utenti SET nome = ?, sesso = ?, data_nascita = ?, area_geografica = ?, occupazione = ?';
        $valori = [$nome, ...self::valoriProfilo($profilo)];
        if ($passwordHash !== null) {
            $sql .= ', password_hash = ?';
            $valori[] = $passwordHash;
        }
        $sql .= " WHERE id = ? AND ruolo = 'UTENTE'";
        $valori[] = $id;

        $query = Database::connessione()->prepare($sql);
        $query->execute($valori);
        return $query->rowCount() === 1;
    }

    private static function valoriProfilo(array $profilo): array
    {
        return array_map(fn($campo) => $profilo[$campo] ?? null, self::PROFILO);
    }
}
