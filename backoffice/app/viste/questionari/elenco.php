<?php
/**
 * Variabili passate da vista() in app/funzioni.php (dichiarate qui per l'editor):
 * @var array $questionari
 * @var array $cifre
 */
?>
<div class="testata">
    <div>
        <h1 data-titolo>Questionari</h1>
        <p class="tenue" data-anima>Crea una bozza, pubblicala quando è pronta, chiudila quando hai raccolto le risposte.</p>
    </div>
    <a class="btn btn-primario" href="<?= e(url('questionario-nuovo')) ?>" data-anima="scala">Nuovo questionario</a>
</div>

<?php if (!$questionari): ?>
    <section class="scheda vuoto" data-anima>
        <h2>Ancora nessun questionario</h2>
        <p class="tenue">Il primo nasce come bozza: puoi cambiarlo quanto vuoi, gli utenti lo vedono solo dopo la pubblicazione.</p>
        <p><a class="btn btn-primario" href="<?= e(url('questionario-nuovo')) ?>">Crea il primo questionario</a></p>
    </section>
<?php else: ?>
    <!-- fascia con i numeri: data-conta li fa "contare" da zero -->
    <dl class="cifre" data-anima-gruppo>
        <div data-anima><dd class="numeri" data-conta="<?= (int) $cifre['BOZZA'] ?>"><?= (int) $cifre['BOZZA'] ?></dd><dt>In bozza</dt></div>
        <div data-anima><dd class="numeri" data-conta="<?= (int) $cifre['PUBBLICATO'] ?>"><?= (int) $cifre['PUBBLICATO'] ?></dd><dt>Pubblicati</dt></div>
        <div data-anima><dd class="numeri" data-conta="<?= (int) $cifre['CHIUSO'] ?>"><?= (int) $cifre['CHIUSO'] ?></dd><dt>Chiusi</dt></div>
        <div data-anima><dd class="numeri" data-conta="<?= (int) $cifre['compilazioni'] ?>"><?= (int) $cifre['compilazioni'] ?></dd><dt>Compilazioni ricevute</dt></div>
    </dl>

    <!-- filtro per stato: lavora nel browser, senza ricaricare la pagina (vedi backoffice.js) -->
    <div class="filtri" role="group" aria-label="Filtra per stato" data-anima>
        <button type="button" class="filtro" data-filtro="" aria-pressed="true">Tutti</button>
        <button type="button" class="filtro" data-filtro="BOZZA" aria-pressed="false">Bozza</button>
        <button type="button" class="filtro" data-filtro="PUBBLICATO" aria-pressed="false">Pubblicato</button>
        <button type="button" class="filtro" data-filtro="CHIUSO" aria-pressed="false">Chiuso</button>
    </div>

    <div class="tabella-wrap" data-anima>
        <table class="tabella">
            <thead>
                <tr>
                    <th scope="col">Titolo</th>
                    <th scope="col">Stato</th>
                    <th scope="col">Destinatari</th>
                    <th scope="col" class="destra">Domande</th>
                    <th scope="col" class="destra">Compilazioni</th>
                    <th scope="col">Creato il</th>
                </tr>
            </thead>
            <tbody data-righe-filtrabili>
                <?php foreach ($questionari as $q): ?>
                    <tr data-stato="<?= e($q['stato']) ?>">
                        <td>
                            <a class="titolo-riga" href="<?= e(url('questionario', ['id' => $q['id']])) ?>"><?= e($q['titolo']) ?></a>
                        </td>
                        <td><?= badgeStato($q['stato']) ?></td>
                        <td class="cella-destinatari">
                            <?php $filtri = descriviDestinatari(destinatariDi($q)); ?>
                            <?php if (!$filtri): ?>
                                <span class="tenue">Tutti</span>
                            <?php else: ?>
                                <span><?= e(plurale((int) $q['num_destinatari'], 'utente', 'utenti')) ?></span>
                                <span class="piccolo tenue"><?= e(implode(' · ', $filtri)) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="destra numeri"><?= (int) $q['num_domande'] ?></td>
                        <td class="destra numeri"><?= (int) $q['num_compilazioni'] ?></td>
                        <td class="tenue"><?= e(formattaData($q['creato_il'], false)) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p class="tabella-vuota tenue" data-nessuna-riga hidden>Nessun questionario in questo stato.</p>
    </div>
<?php endif; ?>
