<?php
/**
 * Variabili passate da vista() in app/funzioni.php (dichiarate qui per l'editor):
 * @var array|null $admin
 * @var array $riepilogo
 * @var array $andamento
 * @var array $ultime
 * @var array $profiliIncompleti
 * @var array $aperti
 * @var array $daCompletare
 * @var array $daFare
 * @var int $numBozze
 */
?>
<?php
// ---- preparo i numeri per i due grafici ----
// Le barre sono disegnate in SVG con altezze calcolate qui in PHP:
// la CSP vieta gli attributi style, ma width/height di un SVG sono attributi normali.
$massimo = max(1, ...array_values($andamento));
$totale14 = array_sum($andamento);
$giorni = array_keys($andamento);

$totCanali = $riepilogo['canale_web'] + $riepilogo['canale_mobile'];
$percWeb = $totCanali > 0 ? (int) round($riepilogo['canale_web'] / $totCanali * 100) : 0;
$percMobile = $totCanali > 0 ? 100 - $percWeb : 0;

$niente = !$daFare && !$daCompletare && $riepilogo['profili_incompleti'] === 0;
?>
<div class="testata">
    <div class="testata-testo">
        <p class="tenue" data-anima><?= e(formattaData(date('Y-m-d'), false)) ?></p>
        <h1 data-titolo>Ciao, <?= e($admin['nome']) ?></h1>
        <p class="tenue" data-anima>Cosa sta succedendo ai tuoi questionari e cosa c'è da fare.</p>
    </div>
    <a class="btn btn-primario" href="<?= e(url('questionario-nuovo')) ?>" data-anima="scala">Nuovo questionario</a>
</div>

<!-- fascia con i numeri principali -->
<dl class="cifre" data-anima-gruppo>
    <div data-anima>
        <dd class="numeri" data-conta="<?= count($aperti) ?>"><?= count($aperti) ?></dd>
        <dt>Questionari aperti</dt>
    </div>
    <div data-anima>
        <dd class="numeri" data-conta="<?= $riepilogo['compilazioni_7gg'] ?>"><?= $riepilogo['compilazioni_7gg'] ?></dd>
        <dt>Compilazioni negli ultimi 7 giorni <span class="cifre-sotto"><?= $riepilogo['compilazioni'] ?> in tutto</span></dt>
    </div>
    <div data-anima>
        <dd class="numeri" data-conta="<?= $riepilogo['utenti'] ?>"><?= $riepilogo['utenti'] ?></dd>
        <dt>Utenti registrati</dt>
    </div>
    <div data-anima>
        <dd class="numeri" data-conta="<?= $numBozze ?>"><?= $numBozze ?></dd>
        <dt>Bozze in lavorazione</dt>
    </div>
</dl>

<!-- DA FARE: la parte piu utile, dice all'admin dove intervenire -->
<section class="pila" aria-labelledby="titolo-da-fare">
    <h2 id="titolo-da-fare" data-anima>Da fare</h2>

    <?php if ($niente): ?>
        <p class="avviso avviso-ok" data-anima>Tutto in ordine: nessuna bozza in sospeso e nessun questionario da sistemare.</p>
    <?php else: ?>
        <ul class="da-fare" data-anima-gruppo>
            <?php foreach ($daFare as $voce): ?>
                <li class="avviso avviso-<?= e($voce['tipo']) ?>" data-anima>
                    <div>
                        <a href="<?= e(url('questionario', ['id' => $voce['questionario']['id']])) ?>"><?= e($voce['questionario']['titolo']) ?></a>
                        <span class="da-fare-testo"><?= e($voce['testo']) ?></span>
                    </div>
                </li>
            <?php endforeach; ?>

            <?php foreach ($daCompletare as $q): ?>
                <li class="avviso" data-anima>
                    <div>
                        <a href="<?= e(url('questionario', ['id' => $q['id']])) ?>"><?= e($q['titolo']) ?></a>
                        <span class="da-fare-testo">
                            Bozza da completare: <?= e($q['problemi'][0]) ?>
                            <?php if (count($q['problemi']) > 1): ?>
                                (e <?= e(plurale(count($q['problemi']) - 1, 'altro problema', 'altri problemi')) ?>)
                            <?php endif; ?>
                        </span>
                    </div>
                </li>
            <?php endforeach; ?>

            <?php if ($riepilogo['profili_incompleti'] > 0): ?>
                <li class="avviso" data-anima>
                    <div>
                        <a href="<?= e(url('utenti')) ?>"><?= e(plurale($riepilogo['profili_incompleti'], 'utente con il profilo incompleto', 'utenti con il profilo incompleto')) ?></a>
                        <span class="da-fare-testo">
                            Non vedono i questionari che filtrano sui dati mancanti
                            (<?= e(implode(', ', array_column($profiliIncompleti, 'username'))) ?><?= $riepilogo['profili_incompleti'] > count($profiliIncompleti) ? '…' : '' ?>).
                        </span>
                    </div>
                </li>
            <?php endif; ?>
        </ul>
    <?php endif; ?>
</section>

<div class="dash-griglia">
    <!-- questionari pubblicati: quanti destinatari hanno gia risposto -->
    <section class="scheda pila" data-luce data-anima aria-labelledby="titolo-aperti">
        <div class="dash-testa">
            <h2 id="titolo-aperti">Questionari aperti</h2>
            <a class="piccolo" href="<?= e(url('questionari')) ?>">Tutti i questionari</a>
        </div>

        <?php if (!$aperti): ?>
            <p class="tenue">Nessun questionario pubblicato in questo momento. Gli utenti non hanno niente da compilare.</p>
        <?php else: ?>
            <ul class="avanzamenti">
                <?php foreach ($aperti as $q): ?>
                    <?php $dest = (int) $q['num_destinatari']; $comp = (int) $q['num_compilazioni']; ?>
                    <li>
                        <div class="avanzamento-testa">
                            <a class="titolo-riga" href="<?= e(url('compilazioni', ['questionario' => $q['id']])) ?>"><?= e($q['titolo']) ?></a>
                            <b class="numeri"><?= $q['percentuale'] ?>%</b>
                        </div>
                        <svg class="barra" viewBox="0 0 100 6" preserveAspectRatio="none" role="img"
                             aria-label="<?= e($comp . ' compilazioni su ' . $dest . ' destinatari') ?>">
                            <rect class="barra-binario" width="100" height="6" rx="3"/>
                            <rect class="barra-pieno" width="<?= $q['percentuale'] ?>" height="6" rx="3"/>
                        </svg>
                        <span class="piccolo tenue">
                            <?= e($comp . ' su ' . plurale($dest, 'destinatario', 'destinatari')) ?>
                            · pubblicato il <?= e(formattaData($q['pubblicato_il'], false)) ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <!-- andamento delle compilazioni e canale di provenienza -->
    <section class="scheda pila" data-luce data-anima aria-labelledby="titolo-andamento">
        <div class="dash-testa">
            <h2 id="titolo-andamento">Andamento</h2>
            <span class="piccolo tenue">ultimi 14 giorni</span>
        </div>

        <figure class="grafico">
            <svg viewBox="0 0 280 90" role="img"
                 aria-label="<?= e('Compilazioni negli ultimi 14 giorni: ' . $totale14 . ' in tutto') ?>">
                <?php foreach ($giorni as $i => $giorno): ?>
                    <?php
                    $valore = $andamento[$giorno];
                    // le barre vuote restano un trattino basso, cosi si vede che il giorno c'e'
                    $altezza = $valore > 0 ? max(4, (int) round($valore / $massimo * 82)) : 2;
                    ?>
                    <rect class="<?= $valore > 0 ? 'colonna' : 'colonna colonna-vuota' ?>"
                          x="<?= $i * 20 + 3 ?>" y="<?= 88 - $altezza ?>" width="14" height="<?= $altezza ?>" rx="3">
                        <title><?= e(formattaData($giorno, false) . ': ' . plurale($valore, 'compilazione', 'compilazioni')) ?></title>
                    </rect>
                <?php endforeach; ?>
            </svg>
            <figcaption class="grafico-assi piccolo tenue">
                <span><?= e(formattaData($giorni[0], false)) ?></span>
                <span>oggi</span>
            </figcaption>
        </figure>

        <div class="pila canali">
            <h3 class="piccolo">Da dove arrivano</h3>
            <?php if ($totCanali === 0): ?>
                <p class="piccolo tenue">Ancora nessuna compilazione.</p>
            <?php else: ?>
                <svg class="barra barra-canali" viewBox="0 0 100 8" preserveAspectRatio="none" role="img"
                     aria-label="<?= e("Portale web {$percWeb}%, app mobile {$percMobile}%") ?>">
                    <rect class="barra-web" width="<?= $percWeb ?>" height="8"/>
                    <rect class="barra-mobile" x="<?= $percWeb ?>" width="<?= $percMobile ?>" height="8"/>
                </svg>
                <ul class="legenda piccolo">
                    <li><span class="punto punto-web" aria-hidden="true"></span>Portale web <b class="numeri"><?= $riepilogo['canale_web'] ?></b></li>
                    <li><span class="punto punto-mobile" aria-hidden="true"></span>App mobile <b class="numeri"><?= $riepilogo['canale_mobile'] ?></b></li>
                </ul>
            <?php endif; ?>
        </div>
    </section>
</div>

<!-- ultime compilazioni da tutti i questionari -->
<section class="pila" aria-labelledby="titolo-ultime">
    <h2 id="titolo-ultime" data-anima>Ultime compilazioni</h2>
    <?php if (!$ultime): ?>
        <p class="tenue" data-anima>Quando un utente invia le risposte dal portale o dall'app, compare qui.</p>
    <?php else: ?>
        <div class="tabella-wrap" data-anima>
            <table class="tabella">
                <thead>
                    <tr>
                        <th scope="col">Utente</th>
                        <th scope="col">Questionario</th>
                        <th scope="col">Canale</th>
                        <th scope="col">Inviata il</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ultime as $c): ?>
                        <tr>
                            <td>
                                <a class="titolo-riga" href="<?= e(url('compilazione', ['id' => $c['id']])) ?>"><?= e($c['nome']) ?></a>
                                <span class="piccolo tenue cella-sotto"><?= e($c['username']) ?></span>
                            </td>
                            <td><a href="<?= e(url('questionario', ['id' => $c['questionario_id']])) ?>"><?= e($c['titolo']) ?></a></td>
                            <td><span class="badge badge-neutro"><?= $c['canale'] === 'MOBILE' ? 'App mobile' : 'Portale web' ?></span></td>
                            <td class="tenue numeri"><?= e(formattaData($c['inviata_il'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
