<?php
/**
 * Pezzo della pagina di dettaglio (incluso da dettaglio.php), solo per
 * questionari pubblicati o chiusi con almeno una compilazione.
 * @var array $statistiche
 * @var int $numDestinatari
 * @var int $id
 */
?>
<?php
$r = $statistiche['riepilogo'];
// tasso di risposta: compilazioni sui destinatari di oggi (bloccato a 100,
// perche' chi ha cambiato profilo dopo aver risposto puo non rientrarci piu)
$tasso = $numDestinatari > 0 ? min(100, (int) round($r['totale'] / $numDestinatari * 100)) : null;
$percWeb = (int) round($r['web'] / $r['totale'] * 100);
?>
<section class="pila statistiche" aria-labelledby="titolo-statistiche">
    <div class="testata testata-sezione" data-anima>
        <div>
            <h2 id="titolo-statistiche">Statistiche</h2>
            <p class="tenue piccolo">Calcolate su <?= e(plurale($r['totale'], 'compilazione', 'compilazioni')) ?>. I gruppi usano il profilo attuale degli utenti.</p>
        </div>
        <a class="btn" href="<?= e(url('compilazioni', ['questionario' => $id])) ?>">Leggi le singole risposte</a>
    </div>

    <dl class="cifre" data-anima-gruppo>
        <div data-anima><dd class="numeri" data-conta="<?= $r['totale'] ?>"><?= $r['totale'] ?></dd><dt>Compilazioni</dt></div>
        <div data-anima>
            <dd class="numeri"><?= $tasso === null ? '–' : $tasso . '%' ?></dd>
            <dt>Tasso di risposta <span class="cifre-sotto">sui <?= $numDestinatari ?> destinatari di oggi</span></dt>
        </div>
        <div data-anima><dd class="numeri" data-conta="<?= $r['web'] ?>"><?= $r['web'] ?></dd><dt>Dal portale web</dt></div>
        <div data-anima><dd class="numeri" data-conta="<?= $r['mobile'] ?>"><?= $r['mobile'] ?></dd><dt>Dall'app mobile</dt></div>
    </dl>

    <svg class="barra barra-canali" viewBox="0 0 100 8" preserveAspectRatio="none" role="img"
         aria-label="<?= e('Portale web ' . $percWeb . '%, app mobile ' . (100 - $percWeb) . '%') ?>">
        <rect class="barra-web" width="<?= $percWeb ?>" height="8"/>
        <rect class="barra-mobile" x="<?= $percWeb ?>" width="<?= 100 - $percWeb ?>" height="8"/>
    </svg>

    <ol class="stat-domande" data-anima-gruppo>
        <?php foreach ($statistiche['domande'] as $indice => $d): ?>
            <li class="scheda pila" data-anima>
                <div class="stat-testa">
                    <span class="domanda-numero numeri" aria-hidden="true"><?= $indice + 1 ?></span>
                    <div>
                        <h3><?= e($d['testo']) ?></h3>
                        <p class="piccolo tenue">
                            <?= e(etichettaTipologia($d['tipologia'])) ?> ·
                            <?= e($d['rispondenti'] . ' su ' . plurale($r['totale'], 'compilazione', 'compilazioni') . ' hanno risposto') ?>
                        </p>
                    </div>
                </div>

                <?php if ($d['tipologia'] === 'APERTA'): ?>
                    <?php if (!$d['testi']): ?>
                        <p class="tenue">Nessuno ha risposto a questa domanda.</p>
                    <?php else: ?>
                        <p class="piccolo tenue">Le risposte più recenti</p>
                        <ul class="stat-citazioni">
                            <?php foreach ($d['testi'] as $testo): ?>
                                <li><?= e(mb_strimwidth($testo, 0, 220, '…')) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                <?php else: ?>
                    <!-- distribuzione delle scelte: barre in SVG, la lunghezza la decide PHP -->
                    <ul class="avanzamenti">
                        <?php foreach ($d['opzioni'] as $o): ?>
                            <li>
                                <div class="avanzamento-testa">
                                    <span><?= e($o['testo']) ?><?php if ($o['top']): ?> <span class="badge badge-pubblicato">più scelta</span><?php endif; ?></span>
                                    <b class="numeri"><?= $o['perc'] ?>% <span class="tenue piccolo">(<?= $o['n'] ?>)</span></b>
                                </div>
                                <svg class="barra" viewBox="0 0 100 6" preserveAspectRatio="none" role="img"
                                     aria-label="<?= e($o['testo'] . ': ' . $o['perc'] . '%') ?>">
                                    <rect class="barra-binario" width="100" height="6" rx="3"/>
                                    <rect class="<?= $o['top'] ? 'barra-pieno barra-top' : 'barra-pieno' ?>" width="<?= $o['perc'] ?>" height="6" rx="3"/>
                                </svg>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if ($d['tipologia'] === 'MULTIPLA'): ?>
                        <p class="piccolo tenue">Ognuno poteva scegliere più opzioni: le percentuali sono sulle persone che hanno risposto e la somma può superare 100.</p>
                    <?php endif; ?>

                    <!-- confronto per gruppi: chiuso di default, cosi la pagina resta leggibile -->
                    <?php if ($d['gruppi']): ?>
                        <details class="stat-gruppi">
                            <summary>Cosa hanno scelto i diversi gruppi</summary>
                            <div class="tabella-wrap">
                                <table class="tabella">
                                    <thead>
                                        <tr>
                                            <th scope="col">Gruppo</th>
                                            <th scope="col" class="destra">Risposte</th>
                                            <th scope="col">Scelta più frequente</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $ultimaCategoria = ''; ?>
                                        <?php foreach ($d['gruppi'] as $g): ?>
                                            <tr>
                                                <td>
                                                    <?php if ($g['categoria'] !== $ultimaCategoria): ?>
                                                        <span class="piccolo tenue cella-sotto"><?= e($g['categoria']) ?></span>
                                                        <?php $ultimaCategoria = $g['categoria']; ?>
                                                    <?php endif; ?>
                                                    <?= e($g['valore']) ?>
                                                </td>
                                                <td class="destra numeri"><?= $g['rispondenti'] ?></td>
                                                <td>
                                                    <?= e($g['scelta']) ?>
                                                    <span class="tenue piccolo">(<?= $g['parita'] ? 'a pari merito, ' : '' ?><?= $g['perc'] ?>%)</span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <p class="piccolo tenue">Chi non ha indicato un dato nel profilo non compare nel gruppo corrispondente.</p>
                        </details>
                    <?php endif; ?>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>
</section>
