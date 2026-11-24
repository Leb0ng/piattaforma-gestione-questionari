<?php
/**
 * Variabili passate da vista() in app/funzioni.php (dichiarate qui per l'editor):
 * @var string $nomeApp
 * @var array|null $admin
 * @var array $messaggi
 */
?>
<?php // Parte finale comune: chiusura del contenuto, footer, messaggi e script. ?>
<?php if ($admin): ?>
            <footer class="bo-piede piccolo tenue">
                <?= e($nomeApp) ?>, Back Office. Project Work di Leon Sciurti.
            </footer>
        </div>
    </main>
<?php endif; ?>

<?php if ($messaggi): ?>
    <div class="toast-area" role="status" aria-live="polite">
        <?php foreach ($messaggi as $messaggio): ?>
            <div class="toast<?= $messaggio['tipo'] === 'errore' ? ' toast-errore' : '' ?>"><?= e($messaggio['testo']) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

    <script src="../stile/js/animazioni.js"></script>
    <script src="assets/backoffice.js"></script>
</body>
</html>
