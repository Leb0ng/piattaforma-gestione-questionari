<?php
/**
 * Variabili passate da vista() in app/funzioni.php (dichiarate qui per l'editor):
 * @var string $titoloPagina
 * @var string $nomeApp
 * @var array|null $admin
 */
?>
<?php
// Parte iniziale comune a tutte le pagine: <head>, menu laterale e apertura del contenuto.
// Chi non ha fatto l'accesso vede una pagina "nuda" (login o errore), senza menu.
$paginaCorrente = is_string($_GET['p'] ?? null) ? $_GET['p'] : 'dashboard';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titoloPagina !== '' ? $titoloPagina . ' | ' . $nomeApp : $nomeApp) ?></title>
    <link rel="preload" href="../stile/font/plus-jakarta-sans-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="icon" href="../stile/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="../stile/css/stile.css">
    <link rel="stylesheet" href="assets/backoffice.css">
</head>
<body class="<?= $admin ? 'bo' : 'bo-accesso' ?>">
<?php if ($admin): ?>
    <a class="salta" href="#contenuto">Vai al contenuto</a>
    <?php require __DIR__ . '/menu.php'; ?>
    <main id="contenuto" class="bo-main">
        <canvas class="bo-decoro" data-costellazione aria-hidden="true"></canvas>
        <div class="bo-colonna">
<?php endif; ?>
