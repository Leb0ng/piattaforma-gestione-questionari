<?php
declare(strict_types=1);

// ---------------------------------------------------------------
// Funzioni di servizio usate da controller e viste
// ---------------------------------------------------------------

// Escape per l'HTML: TUTTO quello che arriva dal db o dall'utente passa da qui
// prima di essere stampato, cosi nessuno puo iniettare codice nella pagina (XSS).
function e(?string $testo): string
{
    return htmlspecialchars($testo ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Costruisce l'indirizzo di una pagina: url('questionario', ['id' => 3]) -> index.php?p=questionario&id=3
function url(string $pagina, array $parametri = []): string
{
    return 'index.php?' . http_build_query(['p' => $pagina] + $parametri);
}

// Reindirizza a un'altra pagina e ferma lo script
function vai(string $pagina, array $parametri = []): never
{
    header('Location: ' . url($pagina, $parametri));
    exit;
}

// Messaggio "usa e getta": lo salvo in sessione e lo mostro nella pagina successiva
function flash(string $tipo, string $testo): void
{
    $_SESSION['flash'][] = ['tipo' => $tipo, 'testo' => $testo];
}

function prendiFlash(): array
{
    $messaggi = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messaggi;
}

// ---------- protezione CSRF ----------
// Ogni form contiene un codice segreto legato alla sessione. Un altro sito non lo
// conosce, quindi non puo far partire azioni al posto dell'admin a sua insaputa.
function csrfToken(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrfCampo(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrfToken()) . '">';
}

// Le azioni che modificano i dati accettano solo POST con il codice giusto
function richiediPost(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Metodo non consentito');
    }
    $inviato = $_POST['csrf'] ?? '';
    if (!is_string($inviato) || !hash_equals(csrfToken(), $inviato)) {
        http_response_code(403);
        exit('Richiesta non valida: ricarica la pagina e riprova.');
    }
}

// ---------- accesso ----------
function adminLoggato(): ?array
{
    return $_SESSION['admin'] ?? null;
}

function richiediAdmin(): array
{
    $admin = adminLoggato();
    if ($admin === null) {
        vai('login');
    }
    return $admin;
}

// ---------- lettura sicura dei parametri ----------
function idDa(array $sorgente, string $nome): int
{
    $valore = filter_var($sorgente[$nome] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $valore === false || $valore === null ? 0 : $valore;
}

function testoDa(array $sorgente, string $nome): string
{
    $valore = $sorgente[$nome] ?? '';
    return is_string($valore) ? trim($valore) : '';
}

// ---------- viste ----------
// Carica una vista dentro il layout comune (header + menu + footer).
// $dati diventa un insieme di variabili disponibili nel file della vista.
function vista(string $__nome, array $__dati = [], string $titoloPagina = ''): void
{
    $config = require __DIR__ . '/../config/config.php';
    $nomeApp = $config['nome_app'];
    $admin = adminLoggato();
    $messaggi = prendiFlash();
    // i nomi con i due trattini bassi evitano scontri con le variabili delle viste
    // (una vista puo ricevere una voce chiamata proprio "dati" o "nome")
    extract($__dati, EXTR_SKIP);

    require __DIR__ . '/viste/layout/header.php';
    require __DIR__ . '/viste/' . $__nome . '.php';
    require __DIR__ . '/viste/layout/footer.php';
}

function nonTrovato(string $messaggio = 'La pagina che cerchi non esiste.'): never
{
    http_response_code(404);
    vista('errore', ['messaggio' => $messaggio], 'Non trovato');
    exit;
}

// ---------- etichette ----------
function etichettaStato(string $stato): string
{
    return ['BOZZA' => 'Bozza', 'PUBBLICATO' => 'Pubblicato', 'CHIUSO' => 'Chiuso'][$stato] ?? $stato;
}

function badgeStato(string $stato): string
{
    return '<span class="badge badge-' . strtolower(e($stato)) . '">' . e(etichettaStato($stato)) . '</span>';
}

function etichettaTipologia(string $tipologia): string
{
    return [
        'APERTA'   => 'Risposta aperta',
        'SINGOLA'  => 'Risposta singola',
        'MULTIPLA' => 'Risposta multipla',
    ][$tipologia] ?? $tipologia;
}

function formattaData(?string $data, bool $conOra = true): string
{
    if (!$data) {
        return '';
    }
    $mesi = ['gen', 'feb', 'mar', 'apr', 'mag', 'giu', 'lug', 'ago', 'set', 'ott', 'nov', 'dic'];
    $t = strtotime($data);
    $testo = (int) date('j', $t) . ' ' . $mesi[(int) date('n', $t) - 1] . ' ' . date('Y', $t);
    return $conOra ? $testo . ', ' . date('H:i', $t) : $testo;
}

// "1 domanda" / "3 domande"
function plurale(int $numero, string $singolare, string $plurale): string
{
    return $numero . ' ' . ($numero === 1 ? $singolare : $plurale);
}

// ---------- categorie degli utenti (profilo e destinatari) ----------
// Unico elenco usato da form, controlli e viste. I valori sono gli stessi
// delle colonne ENUM/SET nel database e degli enum Java delle API.
//   colonna_utente = dove sta il dato nella tabella utenti
//   colonna_dest   = dove sta il filtro nella tabella questionari
function categorie(): array
{
    return [
        'sesso' => [
            'etichetta'      => 'Sesso',
            'colonna_utente' => 'sesso',
            'colonna_dest'   => 'dest_sesso',
            'valori'         => ['UOMO' => 'Uomo', 'DONNA' => 'Donna', 'ALTRO' => 'Altro'],
        ],
        'eta' => [
            'etichetta'      => 'Fascia d\'età',
            'colonna_utente' => 'data_nascita', // la fascia si calcola, vedi fasciaEta()
            'colonna_dest'   => 'dest_eta',
            'valori'         => ['UNDER_30' => 'Under 30', 'DA_30_A_50' => 'Da 30 a 50 anni', 'OVER_50' => 'Over 50'],
        ],
        'area' => [
            'etichetta'      => 'Area geografica',
            'colonna_utente' => 'area_geografica',
            'colonna_dest'   => 'dest_area',
            'valori'         => ['NORD' => 'Nord', 'CENTRO' => 'Centro', 'SUD_ISOLE' => 'Sud e isole'],
        ],
        'occupazione' => [
            'etichetta'      => 'Condizione lavorativa',
            'colonna_utente' => 'occupazione',
            'colonna_dest'   => 'dest_occupazione',
            'valori'         => ['STUDENTE' => 'Studente', 'LAVORATORE' => 'Lavoratore', 'DISOCCUPATO' => 'Disoccupato', 'PENSIONATO' => 'Pensionato'],
        ],
    ];
}

// Anni compiuti oggi, oppure null se la data manca
function eta(?string $dataNascita): ?int
{
    if (!$dataNascita) {
        return null;
    }
    return (new DateTimeImmutable($dataNascita))->diff(new DateTimeImmutable('today'))->y;
}

// Stessi confini delle API (FasciaEta.java) e della query in QuestionarioModel
function fasciaEta(?string $dataNascita): ?string
{
    $anni = eta($dataNascita);
    if ($anni === null) {
        return null;
    }
    return $anni < 30 ? 'UNDER_30' : ($anni <= 50 ? 'DA_30_A_50' : 'OVER_50');
}

function etichettaValore(string $categoria, ?string $valore): string
{
    return $valore === null ? '' : (categorie()[$categoria]['valori'][$valore] ?? $valore);
}

// Dalla riga di un questionario: ['sesso' => ['DONNA'], 'eta' => [], ...]
// (lista vuota = nessun filtro su quella categoria)
function destinatariDi(array $questionario): array
{
    $destinatari = [];
    foreach (categorie() as $chiave => $categoria) {
        $valore = (string) ($questionario[$categoria['colonna_dest']] ?? '');
        $destinatari[$chiave] = $valore === '' ? [] : explode(',', $valore);
    }
    return $destinatari;
}

// Frasi brevi per riassumere i destinatari: ['Sesso: Donna', 'Fascia d'età: Under 30 o Over 50']
// Lista vuota = il questionario e' per tutti
function descriviDestinatari(array $destinatari): array
{
    $frasi = [];
    foreach (categorie() as $chiave => $categoria) {
        if (!empty($destinatari[$chiave])) {
            $nomi = array_map(fn($v) => $categoria['valori'][$v] ?? $v, $destinatari[$chiave]);
            $frasi[] = $categoria['etichetta'] . ': ' . implode(' o ', $nomi);
        }
    }
    return $frasi;
}
