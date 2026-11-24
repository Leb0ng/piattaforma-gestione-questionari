<?php
declare(strict_types=1);

// Collegamento al database con PDO.
// La connessione viene aperta una volta sola e poi riusata da tutti i modelli.
final class Database
{
    private static ?PDO $pdo = null;

    public static function connessione(): PDO
    {
        if (self::$pdo === null) {
            $config = require __DIR__ . '/../config/config.php';
            $db = $config['db'];

            $dsn = "mysql:host={$db['host']};port={$db['porta']};dbname={$db['nome']};charset=utf8mb4";

            self::$pdo = new PDO($dsn, $db['utente'], $db['password'], [
                // se una query fallisce voglio un'eccezione, non un errore silenzioso
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                // prepared statement veri, fatti da MySQL (i dati non finiscono mai dentro l'SQL)
                PDO::ATTR_EMULATE_PREPARES   => false,
                // rowCount() conta le righe TROVATE dal WHERE, non solo quelle cambiate:
                // mi serve per capire se un UPDATE "...AND stato = 'BOZZA'" ha preso la riga
                PDO::MYSQL_ATTR_FOUND_ROWS   => true,
            ]);
        }
        return self::$pdo;
    }
}
