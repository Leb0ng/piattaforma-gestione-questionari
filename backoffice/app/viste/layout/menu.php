<?php
/**
 * Variabili passate da vista() in app/funzioni.php (dichiarate qui per l'editor):
 * @var string $nomeApp
 * @var array|null $admin
 * @var string $paginaCorrente
 */
?>
<?php
// Menu laterale (su schermi stretti diventa una barra in alto).
// aria-current segna la voce della pagina in cui ci si trova.
$inUtenti = in_array($paginaCorrente, ['utenti', 'utente-nuovo', 'utente-modifica'], true);
$inNuovo = $paginaCorrente === 'questionario-nuovo';
$inDashboard = $paginaCorrente === 'dashboard';
$inQuestionari = !$inUtenti && !$inNuovo && !$inDashboard;
?>
<header class="bo-menu">
    <a class="marchio" href="<?= e(url('dashboard')) ?>">
        <svg class="marchio-segno" viewBox="0 0 28 28" width="28" height="28" aria-hidden="true">
            <circle cx="7" cy="7" r="4.25" fill="none" stroke="currentColor" stroke-width="1.5"/>
            <circle cx="7" cy="21" r="4.25" fill="none" stroke="currentColor" stroke-width="1.5"/>
            <circle cx="21" cy="14" r="4.25" fill="none" stroke="currentColor" stroke-width="1.5"/>
            <circle class="marchio-pieno" cx="21" cy="14" r="2.1"/>
        </svg>
        <span><?= e($nomeApp) ?></span>
    </a>

    <nav class="bo-nav" aria-label="Menu principale">
        <a href="<?= e(url('dashboard')) ?>"<?= $inDashboard ? ' aria-current="page"' : '' ?>>Dashboard</a>
        <a href="<?= e(url('questionari')) ?>"<?= $inQuestionari ? ' aria-current="page"' : '' ?>>Questionari</a>
        <a href="<?= e(url('questionario-nuovo')) ?>"<?= $inNuovo ? ' aria-current="page"' : '' ?>>Nuovo questionario</a>
        <a href="<?= e(url('utenti')) ?>"<?= $inUtenti ? ' aria-current="page"' : '' ?>>Utenti</a>
    </nav>

    <div class="bo-utente">
        <p>
            <span class="piccolo tenue">Accesso come</span><br>
            <?= e($admin['nome']) ?>
        </p>
        <form method="post" action="<?= e(url('logout')) ?>">
            <?= csrfCampo() ?>
            <button type="submit" class="btn btn-piccolo">Esci</button>
        </form>
    </div>
</header>
