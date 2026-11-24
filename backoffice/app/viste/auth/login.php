<?php
/**
 * Variabili passate da vista() in app/funzioni.php (dichiarate qui per l'editor):
 * @var string $nomeApp
 * @var string $errore
 * @var string $username
 */
?>
<div class="accesso">
    <!-- meta' sinistra: la "scena" con la decorazione animata -->
    <section class="accesso-scena" aria-hidden="true">
        <canvas data-costellazione></canvas>
        <div class="accesso-scena-testo">
            <p class="accesso-frase" data-titolo>Dalla bozza alle risposte, in un solo posto.</p>
        </div>
    </section>

    <!-- meta' destra: il form -->
    <main class="accesso-pannello">
        <div class="accesso-form" data-anima-gruppo>
            <p class="marchio" data-anima>
                <svg class="marchio-segno" viewBox="0 0 28 28" width="28" height="28" aria-hidden="true">
                    <circle cx="7" cy="7" r="4.25" fill="none" stroke="currentColor" stroke-width="1.5"/>
                    <circle cx="7" cy="21" r="4.25" fill="none" stroke="currentColor" stroke-width="1.5"/>
                    <circle cx="21" cy="14" r="4.25" fill="none" stroke="currentColor" stroke-width="1.5"/>
                    <circle class="marchio-pieno" cx="21" cy="14" r="2.1"/>
                </svg>
                <span><?= e($nomeApp) ?></span>
            </p>

            <div data-anima>
                <h1>Accesso al Back Office</h1>
                <p class="tenue">Riservato all'amministratore.</p>
            </div>

            <?php if ($errore): ?>
                <p class="avviso avviso-errore" role="alert" data-anima="scala"><?= e($errore) ?></p>
            <?php endif; ?>

            <form method="post" action="<?= e(url('login')) ?>" class="pila" novalidate>
                <?= csrfCampo() ?>
                <div class="campo" data-anima>
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?= e($username) ?>"
                           autocomplete="username" autocapitalize="none" spellcheck="false" required autofocus>
                </div>
                <div class="campo" data-anima>
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" autocomplete="current-password" required>
                </div>
                <div data-anima>
                    <button type="submit" class="btn btn-primario btn-largo">Accedi</button>
                </div>
            </form>
        </div>
    </main>
</div>
