<?php
/**
 * Variabili passate da vista() in app/funzioni.php (dichiarate qui per l'editor):
 * @var array $utenti
 */
?>
<div class="testata">
    <div>
        <h1 data-titolo>Utenti</h1>
        <p class="tenue" data-anima>Il profilo di ogni utente decide quali questionari vede. L'utente lo può completare o cambiare anche dal portale e dall'app.</p>
    </div>
    <a class="btn btn-primario" href="<?= e(url('utente-nuovo')) ?>" data-anima="scala">Nuovo utente</a>
</div>

<?php if (!$utenti): ?>
    <section class="scheda vuoto" data-anima>
        <h2>Ancora nessun utente</h2>
        <p class="tenue">Crea il primo utente: potrà accedere al portale e all'app per compilare i questionari.</p>
        <p><a class="btn btn-primario" href="<?= e(url('utente-nuovo')) ?>">Crea il primo utente</a></p>
    </section>
<?php else: ?>
    <div class="tabella-wrap" data-anima>
        <table class="tabella">
            <thead>
                <tr>
                    <th scope="col">Utente</th>
                    <th scope="col">Sesso</th>
                    <th scope="col">Età</th>
                    <th scope="col">Area</th>
                    <th scope="col">Condizione</th>
                    <th scope="col" class="destra">Compilazioni</th>
                    <th scope="col"><span class="solo-lettori">Azioni</span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($utenti as $u): ?>
                    <?php
                    $anni = eta($u['data_nascita']);
                    $mancante = '<span class="tenue">Non indicato</span>';
                    ?>
                    <tr>
                        <td>
                            <a class="titolo-riga" href="<?= e(url('utente-modifica', ['id' => $u['id']])) ?>"><?= e($u['nome']) ?></a>
                            <span class="piccolo tenue"><?= e($u['username']) ?></span>
                        </td>
                        <td><?= $u['sesso'] ? e(etichettaValore('sesso', $u['sesso'])) : $mancante ?></td>
                        <td>
                            <?php if ($anni === null): ?>
                                <?= $mancante ?>
                            <?php else: ?>
                                <span class="numeri"><?= $anni ?> anni</span>
                                <span class="piccolo tenue cella-sotto"><?= e(etichettaValore('eta', fasciaEta($u['data_nascita']))) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= $u['area_geografica'] ? e(etichettaValore('area', $u['area_geografica'])) : $mancante ?></td>
                        <td><?= $u['occupazione'] ? e(etichettaValore('occupazione', $u['occupazione'])) : $mancante ?></td>
                        <td class="destra numeri"><?= (int) $u['num_compilazioni'] ?></td>
                        <td class="destra"><a class="btn btn-piccolo" href="<?= e(url('utente-modifica', ['id' => $u['id']])) ?>">Modifica</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
