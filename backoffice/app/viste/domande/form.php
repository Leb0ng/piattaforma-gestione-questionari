<?php
/**
 * Variabili passate da vista() in app/funzioni.php (dichiarate qui per l'editor):
 * @var array $questionario
 * @var array|null $domanda
 * @var array $dati
 * @var array $errori
 */
?>
<?php
$inModifica = $domanda !== null;
$questionarioId = (int) $questionario['id'];
$aScelta = $dati['tipologia'] !== 'APERTA';
// mostro sempre almeno due righe per le opzioni
$righe = array_pad($dati['opzioni'], 2, '');
$tipologie = [
    'APERTA'   => ['Risposta aperta', 'L\'utente scrive un testo libero.'],
    'SINGOLA'  => ['Risposta singola', 'L\'utente sceglie una sola opzione.'],
    'MULTIPLA' => ['Risposta multipla', 'L\'utente sceglie una o più opzioni.'],
];
?>
<p data-anima="sfuma"><a class="indietro" href="<?= e(url('questionario', ['id' => $questionarioId])) ?>">Torna a "<?= e($questionario['titolo']) ?>"</a></p>

<div class="testata">
    <div>
        <h1 data-titolo><?= $inModifica ? 'Modifica domanda' : 'Nuova domanda' ?></h1>
    </div>
</div>

<form method="post" class="scheda pila stretto" data-anima data-form-domanda novalidate
      action="<?= e($inModifica ? url('domanda-modifica', ['id' => $domanda['id']]) : url('domanda-nuova', ['questionario' => $questionarioId])) ?>">
    <?= csrfCampo() ?>

    <div class="campo">
        <label for="testo">Testo della domanda</label>
        <textarea id="testo" name="testo" maxlength="500" class="basso" required
                  <?= isset($errori['testo']) ? 'aria-invalid="true" aria-describedby="errore-testo"' : '' ?>><?= e($dati['testo']) ?></textarea>
        <?php if (isset($errori['testo'])): ?>
            <p class="errore-campo" id="errore-testo"><?= e($errori['testo']) ?></p>
        <?php endif; ?>
    </div>

    <fieldset class="campo gruppo">
        <legend class="etichetta">Tipologia</legend>
        <div class="tessere">
            <?php foreach ($tipologie as $valore => [$nome, $spiegazione]): ?>
                <label class="tessera">
                    <input type="radio" name="tipologia" value="<?= e($valore) ?>" <?= $dati['tipologia'] === $valore ? 'checked' : '' ?>>
                    <span><b><?= e($nome) ?></b><small><?= e($spiegazione) ?></small></span>
                </label>
            <?php endforeach; ?>
        </div>
        <?php if (isset($errori['tipologia'])): ?>
            <p class="errore-campo"><?= e($errori['tipologia']) ?></p>
        <?php endif; ?>
    </fieldset>

    <!-- le opzioni servono solo alle domande a scelta: backoffice.js mostra/nasconde questo blocco -->
    <fieldset class="campo gruppo" data-blocco-opzioni <?= $aScelta ? '' : 'hidden' ?>>
        <legend class="etichetta">Opzioni di risposta</legend>
        <p class="aiuto">Almeno due. Le righe lasciate vuote vengono ignorate.</p>
        <ul class="righe-opzioni" data-righe-opzioni>
            <?php foreach ($righe as $indice => $testo): ?>
                <li class="riga-opzione">
                    <input type="text" class="input" name="opzioni[]" maxlength="255" value="<?= e($testo) ?>"
                           aria-label="Opzione <?= $indice + 1 ?>" placeholder="Opzione <?= $indice + 1 ?>">
                    <button type="button" class="btn btn-piccolo btn-icona" data-togli-opzione aria-label="Togli questa opzione">
                        <svg viewBox="0 0 16 16" width="16" height="16" aria-hidden="true"><path d="M4 4l8 8M12 4l-8 8" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                    </button>
                </li>
            <?php endforeach; ?>
        </ul>
        <div>
            <button type="button" class="btn btn-piccolo" data-aggiungi-opzione>Aggiungi opzione</button>
        </div>
        <?php if (isset($errori['opzioni'])): ?>
            <p class="errore-campo"><?= e($errori['opzioni']) ?></p>
        <?php endif; ?>
    </fieldset>

    <label class="interruttore">
        <input type="checkbox" name="obbligatoria" value="1" <?= $dati['obbligatoria'] ? 'checked' : '' ?>>
        <i aria-hidden="true"></i>
        <span>Risposta obbligatoria</span>
    </label>

    <div class="azioni-form">
        <button type="submit" class="btn btn-primario"><?= $inModifica ? 'Salva domanda' : 'Aggiungi domanda' ?></button>
        <a class="btn" href="<?= e(url('questionario', ['id' => $questionarioId])) ?>">Annulla</a>
    </div>
</form>
