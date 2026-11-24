<?php
/**
 * Variabili passate da vista() in app/funzioni.php (dichiarate qui per l'editor):
 * @var array $questionario
 * @var array $domande
 * @var array $problemi
 * @var array|null $statistiche
 */
?>
<?php
$id = (int) $questionario['id'];
$stato = $questionario['stato'];
$inBozza = $stato === 'BOZZA';
$numCompilazioni = (int) $questionario['num_compilazioni'];
$numDestinatari = (int) $questionario['num_destinatari'];
$filtri = descriviDestinatari(destinatariDi($questionario));

// a che punto del percorso Bozza -> Pubblicato -> Chiuso si trova (0, 1, 2)
$passo = ['BOZZA' => 0, 'PUBBLICATO' => 1, 'CHIUSO' => 2][$stato];
$tappe = [
    ['nome' => 'Bozza',      'data' => formattaData($questionario['creato_il']),     'nota' => 'Visibile solo a te, modificabile.'],
    ['nome' => 'Pubblicato', 'data' => formattaData($questionario['pubblicato_il']), 'nota' => 'Aperto alla compilazione, struttura bloccata.'],
    ['nome' => 'Chiuso',     'data' => formattaData($questionario['chiuso_il']),     'nota' => 'Non accetta più risposte.'],
];
?>
<p data-anima="sfuma"><a class="indietro" href="<?= e(url('questionari')) ?>">Torna ai questionari</a></p>

<div class="testata">
    <div class="testata-testo">
        <p data-anima><?= badgeStato($stato) ?></p>
        <h1 data-titolo><?= e($questionario['titolo']) ?></h1>
        <p class="descrizione tenue" data-anima><?= nl2br(e($questionario['descrizione'])) ?></p>
    </div>
</div>

<!-- il percorso dei tre stati: e' una sequenza vera, per questo e' un elenco ordinato -->
<ol class="percorso" data-anima-gruppo aria-label="Stato del questionario">
    <?php foreach ($tappe as $indice => $tappa): ?>
        <li class="<?= $indice < $passo ? 'fatta' : ($indice === $passo ? 'attuale' : 'futura') ?>" data-anima
            <?= $indice === $passo ? 'aria-current="step"' : '' ?>>
            <b><?= e($tappa['nome']) ?></b>
            <span class="piccolo tenue"><?= $indice <= $passo && $tappa['data'] !== '' ? e($tappa['data']) : e($tappa['nota']) ?></span>
        </li>
    <?php endforeach; ?>
</ol>

<!-- le azioni disponibili cambiano in base allo stato -->
<section class="scheda azioni-stato" data-luce data-anima aria-label="Azioni">
    <?php if ($inBozza): ?>
        <div>
            <h2>Pronto per essere pubblicato?</h2>
            <?php if ($problemi): ?>
                <ul class="lista-problemi">
                    <?php foreach ($problemi as $problema): ?>
                        <li><?= e($problema) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="tenue">Dopo la pubblicazione titolo, descrizione, destinatari e domande non si possono più cambiare.</p>
            <?php endif; ?>
        </div>
        <div class="gruppo-btn">
            <form method="post" action="<?= e(url('questionario-pubblica')) ?>"
                  data-conferma="<?= e(($filtri ? 'Lo vedranno solo gli utenti che rientrano nei destinatari (oggi ' . plurale($numDestinatari, 'utente', 'utenti') . ').' : 'Tutti gli utenti potranno compilarlo.') . ' Titolo, descrizione, destinatari e domande non si potranno più modificare e il questionario non si potrà più eliminare.') ?>"
                  data-conferma-titolo="Pubblicare il questionario?" data-conferma-azione="Pubblica">
                <?= csrfCampo() ?>
                <input type="hidden" name="id" value="<?= $id ?>">
                <button type="submit" class="btn btn-primario" <?= $problemi ? 'disabled' : '' ?>>Pubblica</button>
            </form>
            <a class="btn" href="<?= e(url('questionario-modifica', ['id' => $id])) ?>">Modifica titolo e destinatari</a>
            <form method="post" action="<?= e(url('questionario-elimina')) ?>"
                  data-conferma="La bozza e tutte le sue domande verranno cancellate. Non si può annullare."
                  data-conferma-titolo="Eliminare la bozza?" data-conferma-azione="Elimina" data-conferma-tipo="pericolo">
                <?= csrfCampo() ?>
                <input type="hidden" name="id" value="<?= $id ?>">
                <button type="submit" class="btn btn-pericolo">Elimina</button>
            </form>
        </div>
    <?php elseif ($stato === 'PUBBLICATO'): ?>
        <div>
            <h2><?= $numCompilazioni === 0 ? 'In attesa delle prime risposte' : e(plurale($numCompilazioni, 'compilazione ricevuta', 'compilazioni ricevute')) ?></h2>
            <p class="tenue">Il questionario è aperto. Quando lo chiudi smette di accettare risposte e non si può riaprire.</p>
        </div>
        <div class="gruppo-btn">
            <a class="btn btn-primario" href="<?= e(url('compilazioni', ['questionario' => $id])) ?>">Vedi le compilazioni</a>
            <form method="post" action="<?= e(url('questionario-chiudi')) ?>"
                  data-conferma="Il questionario non accetterà più compilazioni e non potrà essere riaperto. Le risposte ricevute restano consultabili."
                  data-conferma-titolo="Chiudere la compilazione?" data-conferma-azione="Chiudi">
                <?= csrfCampo() ?>
                <input type="hidden" name="id" value="<?= $id ?>">
                <button type="submit" class="btn">Chiudi la compilazione</button>
            </form>
        </div>
    <?php else: ?>
        <div>
            <h2><?= e(plurale($numCompilazioni, 'compilazione raccolta', 'compilazioni raccolte')) ?></h2>
            <p class="tenue">Il questionario è chiuso. Le risposte restano consultabili da te e, in sola lettura, da chi le ha inviate.</p>
        </div>
        <div class="gruppo-btn">
            <a class="btn btn-primario" href="<?= e(url('compilazioni', ['questionario' => $id])) ?>">Vedi le compilazioni</a>
        </div>
    <?php endif; ?>
</section>

<?php if ($statistiche): ?>
    <?php require __DIR__ . '/statistiche.php'; ?>
<?php endif; ?>

<!-- a chi e' rivolto: riassunto dei filtri e quanti utenti ci rientrano oggi -->
<section class="scheda destinatari-riepilogo" data-anima aria-labelledby="titolo-destinatari">
    <div>
        <h2 id="titolo-destinatari">Destinatari</h2>
        <?php if (!$filtri): ?>
            <p class="tenue">Tutti gli utenti.</p>
        <?php else: ?>
            <ul class="destinatari-filtri">
                <?php foreach ($filtri as $filtro): ?>
                    <li class="badge badge-neutro"><?= e($filtro) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
    <p class="destinatari-conta">
        <b class="numeri"><?= $numDestinatari ?></b>
        <span class="piccolo tenue"><?= $numDestinatari === 1 ? 'utente rientra' : 'utenti rientrano' ?> oggi</span>
    </p>
    <?php if ($numDestinatari === 0 && $stato !== 'CHIUSO'): ?>
        <p class="avviso avviso-info destinatari-avviso">
            Nessun utente rientra in questi destinatari: così com'è, il questionario non lo vedrebbe nessuno.
            Gli utenti che completano o cambiano il profilo in seguito vengono inclusi automaticamente.
        </p>
    <?php endif; ?>
</section>

<section class="pila" aria-labelledby="titolo-domande">
    <div class="testata testata-sezione" data-anima>
        <h2 id="titolo-domande">Domande <span class="tenue numeri"><?= count($domande) ?></span></h2>
        <?php if ($inBozza): ?>
            <a class="btn" href="<?= e(url('domanda-nuova', ['questionario' => $id])) ?>">Aggiungi domanda</a>
        <?php endif; ?>
    </div>

    <?php if (!$inBozza): ?>
        <p class="avviso avviso-info" data-anima>La struttura è bloccata: le domande si possono modificare solo finché il questionario è in bozza.</p>
    <?php endif; ?>

    <?php if (!$domande): ?>
        <div class="scheda vuoto" data-anima>
            <p class="tenue">Nessuna domanda. Aggiungine almeno una per poter pubblicare.</p>
            <p><a class="btn btn-primario" href="<?= e(url('domanda-nuova', ['questionario' => $id])) ?>">Aggiungi la prima domanda</a></p>
        </div>
    <?php else: ?>
        <ol class="domande" data-anima-gruppo>
            <?php foreach ($domande as $indice => $domanda): ?>
                <li class="scheda domanda" data-luce data-anima>
                    <div class="domanda-testa">
                        <span class="domanda-numero numeri" aria-hidden="true"><?= $indice + 1 ?></span>
                        <div class="domanda-corpo">
                            <h3><?= e($domanda['testo']) ?></h3>
                            <p class="domanda-meta">
                                <span class="badge badge-neutro"><?= e(etichettaTipologia($domanda['tipologia'])) ?></span>
                                <span class="badge badge-neutro"><?= $domanda['obbligatoria'] ? 'Obbligatoria' : 'Facoltativa' ?></span>
                            </p>
                            <?php if ($domanda['opzioni']): ?>
                                <ul class="opzioni opzioni-<?= strtolower(e($domanda['tipologia'])) ?>">
                                    <?php foreach ($domanda['opzioni'] as $opzione): ?>
                                        <li><?= e($opzione['testo']) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($inBozza): ?>
                        <div class="domanda-azioni">
                            <form method="post" action="<?= e(url('domanda-sposta')) ?>">
                                <?= csrfCampo() ?>
                                <input type="hidden" name="id" value="<?= (int) $domanda['id'] ?>">
                                <input type="hidden" name="direzione" value="su">
                                <button type="submit" class="btn btn-piccolo btn-icona" aria-label="Sposta su" <?= $indice === 0 ? 'disabled' : '' ?>>
                                    <svg viewBox="0 0 16 16" width="16" height="16" aria-hidden="true"><path d="M8 12.5v-9M4 7l4-4 4 4" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </button>
                            </form>
                            <form method="post" action="<?= e(url('domanda-sposta')) ?>">
                                <?= csrfCampo() ?>
                                <input type="hidden" name="id" value="<?= (int) $domanda['id'] ?>">
                                <input type="hidden" name="direzione" value="giu">
                                <button type="submit" class="btn btn-piccolo btn-icona" aria-label="Sposta giù" <?= $indice === count($domande) - 1 ? 'disabled' : '' ?>>
                                    <svg viewBox="0 0 16 16" width="16" height="16" aria-hidden="true"><path d="M8 3.5v9M4 9l4 4 4-4" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </button>
                            </form>
                            <a class="btn btn-piccolo" href="<?= e(url('domanda-modifica', ['id' => $domanda['id']])) ?>">Modifica</a>
                            <form method="post" action="<?= e(url('domanda-elimina')) ?>"
                                  data-conferma="La domanda e le sue opzioni verranno cancellate."
                                  data-conferma-titolo="Eliminare la domanda?" data-conferma-azione="Elimina" data-conferma-tipo="pericolo">
                                <?= csrfCampo() ?>
                                <input type="hidden" name="id" value="<?= (int) $domanda['id'] ?>">
                                <button type="submit" class="btn btn-piccolo btn-pericolo">Elimina</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>
</section>
