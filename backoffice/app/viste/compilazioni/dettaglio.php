<?php
/**
 * Variabili passate da vista() in app/funzioni.php (dichiarate qui per l'editor):
 * @var array $compilazione
 * @var array $questionario
 * @var array $domande
 * @var array $risposte
 */
?>
<?php $questionarioId = (int) $questionario['id']; ?>
<p data-anima="sfuma"><a class="indietro" href="<?= e(url('compilazioni', ['questionario' => $questionarioId])) ?>">Torna alle compilazioni</a></p>

<div class="testata">
    <div class="testata-testo">
        <p data-anima>
            <span class="badge badge-neutro"><?= $compilazione['canale'] === 'MOBILE' ? 'App mobile' : 'Portale web' ?></span>
        </p>
        <h1 data-titolo>Risposte di <?= e($compilazione['nome']) ?></h1>
        <p class="tenue" data-anima>
            <?= e($questionario['titolo']) ?><br>
            Inviate il <?= e(formattaData($compilazione['inviata_il'])) ?> da <?= e($compilazione['username']) ?>
        </p>
    </div>
</div>

<ol class="domande" data-anima-gruppo>
    <?php foreach ($domande as $indice => $domanda): ?>
        <?php
        $risposta = $risposte[(int) $domanda['id']] ?? ['testo' => null, 'opzioni' => []];
        $vuota = $risposta['testo'] === null && !$risposta['opzioni'];
        ?>
        <li class="scheda domanda" data-anima>
            <div class="domanda-testa">
                <span class="domanda-numero numeri" aria-hidden="true"><?= $indice + 1 ?></span>
                <div class="domanda-corpo">
                    <h3><?= e($domanda['testo']) ?></h3>
                    <p class="domanda-meta">
                        <span class="badge badge-neutro"><?= e(etichettaTipologia($domanda['tipologia'])) ?></span>
                        <span class="badge badge-neutro"><?= $domanda['obbligatoria'] ? 'Obbligatoria' : 'Facoltativa' ?></span>
                    </p>

                    <?php if ($vuota): ?>
                        <p class="tenue">Nessuna risposta.</p>
                    <?php elseif ($domanda['tipologia'] === 'APERTA'): ?>
                        <blockquote class="risposta-testo"><?= nl2br(e($risposta['testo'])) ?></blockquote>
                    <?php else: ?>
                        <!-- mostro tutte le opzioni e segno quelle scelte dall'utente -->
                        <ul class="opzioni opzioni-<?= strtolower(e($domanda['tipologia'])) ?>">
                            <?php foreach ($domanda['opzioni'] as $opzione): ?>
                                <?php $scelta = in_array((int) $opzione['id'], $risposta['opzioni'], true); ?>
                                <li class="<?= $scelta ? 'scelta' : 'non-scelta' ?>">
                                    <?= e($opzione['testo']) ?>
                                    <?php if ($scelta): ?><span class="solo-lettori">(scelta)</span><?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </li>
    <?php endforeach; ?>
</ol>
