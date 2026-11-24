<?php
/**
 * Variabili passate da vista() in app/funzioni.php (dichiarate qui per l'editor):
 * @var array|null $questionario
 * @var array $dati
 * @var array $errori
 */
?>
<?php $inModifica = $questionario !== null; ?>
<p data-anima="sfuma">
    <a class="indietro" href="<?= e($inModifica ? url('questionario', ['id' => $questionario['id']]) : url('questionari')) ?>">
        <?= $inModifica ? 'Torna al questionario' : 'Torna ai questionari' ?>
    </a>
</p>

<div class="testata">
    <div>
        <h1 data-titolo><?= $inModifica ? 'Modifica questionario' : 'Nuovo questionario' ?></h1>
        <p class="tenue" data-anima>
            <?= $inModifica
                ? 'Titolo, descrizione e destinatari si possono cambiare finché il questionario è in bozza.'
                : 'Parti da titolo, descrizione e destinatari. Le domande le aggiungi subito dopo.' ?>
        </p>
    </div>
</div>

<form method="post" class="scheda pila stretto" data-anima novalidate
      action="<?= e($inModifica ? url('questionario-modifica', ['id' => $questionario['id']]) : url('questionario-nuovo')) ?>">
    <?= csrfCampo() ?>

    <div class="campo">
        <label for="titolo">Titolo</label>
        <input type="text" id="titolo" name="titolo" maxlength="200" value="<?= e($dati['titolo']) ?>" required
               <?= isset($errori['titolo']) ? 'aria-invalid="true" aria-describedby="errore-titolo"' : '' ?>>
        <?php if (isset($errori['titolo'])): ?>
            <p class="errore-campo" id="errore-titolo"><?= e($errori['titolo']) ?></p>
        <?php endif; ?>
    </div>

    <div class="campo">
        <label for="descrizione">Descrizione</label>
        <textarea id="descrizione" name="descrizione" maxlength="5000" required
                  <?= isset($errori['descrizione']) ? 'aria-invalid="true" aria-describedby="errore-descrizione"' : 'aria-describedby="aiuto-descrizione"' ?>><?= e($dati['descrizione']) ?></textarea>
        <?php if (isset($errori['descrizione'])): ?>
            <p class="errore-campo" id="errore-descrizione"><?= e($errori['descrizione']) ?></p>
        <?php else: ?>
            <p class="aiuto" id="aiuto-descrizione">Gli utenti la leggono prima di iniziare a compilare.</p>
        <?php endif; ?>
    </div>

    <!-- destinatari: per ogni categoria uno o piu valori (OR), tra categorie vale l'AND -->
    <fieldset class="campo gruppo">
        <legend class="etichetta">Destinatari</legend>
        <p class="aiuto" id="aiuto-destinatari">
            Chi può vedere e compilare il questionario. In ogni categoria puoi scegliere uno o più valori;
            se non ne scegli nessuno, la categoria non filtra. Un utente vede il questionario solo se rientra
            in tutte le categorie che hai filtrato.
        </p>
        <div class="destinatari">
            <?php foreach (categorie() as $chiave => $categoria): ?>
                <div class="destinatari-gruppo" role="group" aria-labelledby="dest-<?= e($chiave) ?>" aria-describedby="aiuto-destinatari">
                    <p class="destinatari-nome" id="dest-<?= e($chiave) ?>"><?= e($categoria['etichetta']) ?></p>
                    <div class="tessere tessere-compatte">
                        <?php foreach ($categoria['valori'] as $valore => $nome): ?>
                            <label class="tessera">
                                <input type="checkbox" name="dest[<?= e($chiave) ?>][]" value="<?= e($valore) ?>"
                                       <?= in_array($valore, $dati['destinatari'][$chiave] ?? [], true) ? 'checked' : '' ?>>
                                <span><b><?= e($nome) ?></b></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </fieldset>

    <div class="azioni-form">
        <button type="submit" class="btn btn-primario"><?= $inModifica ? 'Salva modifiche' : 'Crea bozza' ?></button>
        <a class="btn" href="<?= e($inModifica ? url('questionario', ['id' => $questionario['id']]) : url('questionari')) ?>">Annulla</a>
    </div>
</form>
