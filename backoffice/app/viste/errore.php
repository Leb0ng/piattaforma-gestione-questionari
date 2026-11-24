<?php
/**
 * Variabili passate da vista() in app/funzioni.php (dichiarate qui per l'editor):
 * @var array|null $admin
 * @var string $messaggio
 */
?>
<section class="vuoto<?= $admin ? '' : ' vuoto-centrato' ?>" data-anima>
    <h1>Qualcosa non torna</h1>
    <p class="tenue"><?= e($messaggio) ?></p>
    <p><a class="btn" href="<?= e(url($admin ? 'questionari' : 'login')) ?>"><?= $admin ? 'Torna ai questionari' : 'Vai all\'accesso' ?></a></p>
</section>
