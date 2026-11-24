<?php
declare(strict_types=1);

// =====================================================================
// FRONT CONTROLLER
// Tutte le richieste del Back Office passano da questo file:
//   index.php?p=nome-pagina
// Qui avvio la sessione, carico le classi e decido quale controller chiamare.
// E' il punto dove la "presentazione" (viste) viene tenuta separata dalla
// "logica applicativa" (controller e modelli), come chiede la traccia.
// =====================================================================

// intestazioni di sicurezza: niente iframe da altri siti, e la pagina puo caricare
// script e stili solo dal mio stesso server (niente codice scritto dentro l'HTML)
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; base-uri 'self'; form-action 'self'");

// il cookie di sessione non e' leggibile da JavaScript e non viaggia verso altri siti
session_name('questionari_bo');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();

require __DIR__ . '/app/Database.php';
require __DIR__ . '/app/funzioni.php';
require __DIR__ . '/app/modelli/UtenteModel.php';
require __DIR__ . '/app/modelli/QuestionarioModel.php';
require __DIR__ . '/app/modelli/DomandaModel.php';
require __DIR__ . '/app/modelli/CompilazioneModel.php';
require __DIR__ . '/app/modelli/DashboardModel.php';
require __DIR__ . '/app/modelli/StatisticheModel.php';
require __DIR__ . '/app/controller/AuthController.php';
require __DIR__ . '/app/controller/DashboardController.php';
require __DIR__ . '/app/controller/QuestionariController.php';
require __DIR__ . '/app/controller/DomandeController.php';
require __DIR__ . '/app/controller/CompilazioniController.php';
require __DIR__ . '/app/controller/UtentiController.php';

// pagina => [controller, metodo]. Tutto cio che non e' in elenco non esiste.
$rotte = [
    'login'                 => [AuthController::class, 'login'],
    'logout'                => [AuthController::class, 'logout'],

    'dashboard'             => [DashboardController::class, 'mostra'],

    'questionari'           => [QuestionariController::class, 'elenco'],
    'questionario'          => [QuestionariController::class, 'dettaglio'],
    'questionario-nuovo'    => [QuestionariController::class, 'nuovo'],
    'questionario-modifica' => [QuestionariController::class, 'modifica'],
    'questionario-elimina'  => [QuestionariController::class, 'elimina'],
    'questionario-pubblica' => [QuestionariController::class, 'pubblica'],
    'questionario-chiudi'   => [QuestionariController::class, 'chiudi'],

    'domanda-nuova'         => [DomandeController::class, 'nuova'],
    'domanda-modifica'      => [DomandeController::class, 'modifica'],
    'domanda-elimina'       => [DomandeController::class, 'elimina'],
    'domanda-sposta'        => [DomandeController::class, 'sposta'],

    'compilazioni'          => [CompilazioniController::class, 'elenco'],
    'compilazione'          => [CompilazioniController::class, 'dettaglio'],

    'utenti'                => [UtentiController::class, 'elenco'],
    'utente-nuovo'          => [UtentiController::class, 'nuovo'],
    'utente-modifica'       => [UtentiController::class, 'modifica'],
];

// senza ?p= si arriva alla dashboard
$pagina = $_GET['p'] ?? 'dashboard';
if (!is_string($pagina)) {
    $pagina = '';
}

// l'unica pagina visibile senza aver fatto l'accesso e' il login:
// per tutte le altre, chi non e' admin viene rimandato li
if ($pagina !== 'login') {
    richiediAdmin();
}

if (!isset($rotte[$pagina])) {
    nonTrovato();
}

[$classe, $metodo] = $rotte[$pagina];

try {
    (new $classe())->$metodo();
} catch (PDOException $errore) {
    // problema con il database: lo scrivo nel log del server, all'utente mostro
    // un messaggio comprensibile e NON i dettagli tecnici
    error_log('[Questionari BO] ' . $errore->getMessage());
    http_response_code(500);
    vista('errore', [
        'messaggio' => 'Non riesco a comunicare con il database. Controlla che MySQL sia acceso e che le credenziali in config/config.php siano corrette.',
    ], 'Errore');
}
