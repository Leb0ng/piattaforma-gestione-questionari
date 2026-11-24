<?php
/**
 * Variabili passate da vista() in app/funzioni.php (dichiarate qui per l'editor):
 * @var array $questionario
 * @var array $compilazioni
 */
?>
<?php $id = (int) $questionario['id']; ?>
<p data-anima="sfuma"><a class="indietro" href="<?= e(url('questionario', ['id' => $id])) ?>">Torna a "<?= e($questionario['titolo']) ?>"</a></p>

<div class="testata">
    <div class="testata-testo">
        <p data-anima><?= badgeStato($questionario['stato']) ?></p>
        <h1 data-titolo>Compilazioni ricevute</h1>
        <p class="tenue" data-anima><?= e($questionario['titolo']) ?></p>
    </div>
</div>

<?php if (!$compilazioni): ?>
    <section class="scheda vuoto" data-anima>
        <h2>Nessuna compilazione</h2>
        <p class="tenue">
            <?= $questionario['stato'] === 'BOZZA'
                ? 'Il questionario è ancora in bozza: gli utenti lo vedranno dopo la pubblicazione.'
                : 'Appena un utente invia le risposte dal portale o dall\'app, compare qui.' ?>
        </p>
    </section>
<?php else: ?>
    <div class="tabella-wrap" data-anima>
        <table class="tabella">
            <thead>
                <tr>
                    <th scope="col">Utente</th>
                    <th scope="col">Canale</th>
                    <th scope="col">Inviata il</th>
                    <th scope="col"><span class="solo-lettori">Azioni</span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($compilazioni as $c): ?>
                    <tr>
                        <td>
                            <a class="titolo-riga" href="<?= e(url('compilazione', ['id' => $c['id']])) ?>"><?= e($c['nome']) ?></a>
                            <span class="piccolo tenue"><?= e($c['username']) ?></span>
                        </td>
                        <td><span class="badge badge-neutro"><?= $c['canale'] === 'MOBILE' ? 'App mobile' : 'Portale web' ?></span></td>
                        <td class="tenue numeri"><?= e(formattaData($c['inviata_il'])) ?></td>
                        <td class="destra"><a class="btn btn-piccolo" href="<?= e(url('compilazione', ['id' => $c['id']])) ?>">Leggi le risposte</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
