<?php
/**
 * Variabili passate da vista() in app/funzioni.php (dichiarate qui per l'editor):
 * @var array|null $utente
 * @var array $dati
 * @var array $errori
 */
?>
<?php
$inModifica = $utente !== null;
$categorie = categorie();

// attributi per un campo con o senza errore (accessibilita: l'errore e' collegato al campo)
$attributiErrore = function (string $campo) use ($errori): string {
    return isset($errori[$campo]) ? 'aria-invalid="true" aria-describedby="errore-' . $campo . '"' : '';
};
$messaggioErrore = function (string $campo) use ($errori): string {
    return isset($errori[$campo]) ? '<p class="errore-campo" id="errore-' . $campo . '">' . e($errori[$campo]) . '</p>' : '';
};
// un menu a tendina per una categoria del profilo, con la voce "Non indicato"
$tendina = function (string $campo, string $categoria) use ($dati, $categorie, $attributiErrore, $messaggioErrore): string {
    $html = '<div class="campo"><label for="' . $campo . '">' . e($categorie[$categoria]['etichetta']) . '</label>'
        . '<select id="' . $campo . '" name="' . $campo . '" ' . $attributiErrore($campo) . '>'
        . '<option value="">Non indicato</option>';
    foreach ($categorie[$categoria]['valori'] as $valore => $nome) {
        $html .= '<option value="' . e($valore) . '"' . ($dati[$campo] === $valore ? ' selected' : '') . '>' . e($nome) . '</option>';
    }
    return $html . '</select>' . $messaggioErrore($campo) . '</div>';
};
?>
<p data-anima="sfuma"><a class="indietro" href="<?= e(url('utenti')) ?>">Torna agli utenti</a></p>

<div class="testata">
    <div>
        <h1 data-titolo><?= $inModifica ? 'Modifica utente' : 'Nuovo utente' ?></h1>
        <p class="tenue" data-anima>
            <?= $inModifica
                ? 'Lo username non si può cambiare. Il profilo decide quali questionari l\'utente vede.'
                : 'L\'utente accede al portale e all\'app con username e password. Il profilo si può lasciare incompleto: glielo chiederemo al primo accesso.' ?>
        </p>
    </div>
</div>

<form method="post" class="scheda pila stretto" data-anima novalidate
      action="<?= e($inModifica ? url('utente-modifica', ['id' => $utente['id']]) : url('utente-nuovo')) ?>">
    <?= csrfCampo() ?>

    <div class="griglia-2">
        <div class="campo">
            <label for="username">Username</label>
            <?php if ($inModifica): ?>
                <input type="text" id="username" value="<?= e($dati['username']) ?>" disabled>
            <?php else: ?>
                <input type="text" id="username" name="username" maxlength="50" value="<?= e($dati['username']) ?>" required
                       autocomplete="off" autocapitalize="none" spellcheck="false" <?= $attributiErrore('username') ?>>
                <?= $messaggioErrore('username') ?>
            <?php endif; ?>
        </div>

        <div class="campo">
            <label for="nome">Nome e cognome</label>
            <input type="text" id="nome" name="nome" maxlength="100" value="<?= e($dati['nome']) ?>" required <?= $attributiErrore('nome') ?>>
            <?= $messaggioErrore('nome') ?>
        </div>
    </div>

    <div class="campo">
        <label for="password"><?= $inModifica ? 'Nuova password' : 'Password' ?></label>
        <input type="password" id="password" name="password" autocomplete="new-password" <?= $inModifica ? '' : 'required' ?>
               <?= isset($errori['password']) ? $attributiErrore('password') : 'aria-describedby="aiuto-password"' ?>>
        <?php if (isset($errori['password'])): ?>
            <?= $messaggioErrore('password') ?>
        <?php else: ?>
            <p class="aiuto" id="aiuto-password"><?= $inModifica ? 'Lasciala vuota per non cambiarla.' : 'Almeno 8 caratteri.' ?></p>
        <?php endif; ?>
    </div>

    <fieldset class="campo gruppo">
        <legend class="etichetta">Profilo</legend>
        <p class="aiuto">Usato per scegliere i destinatari dei questionari. Un dato non indicato esclude l'utente dai questionari filtrati su quel dato.</p>
        <div class="griglia-2">
            <?= $tendina('sesso', 'sesso') ?>

            <div class="campo">
                <label for="data_nascita">Data di nascita</label>
                <input type="date" id="data_nascita" name="data_nascita" value="<?= e($dati['data_nascita']) ?>"
                       max="<?= e(date('Y-m-d')) ?>" <?= $attributiErrore('data_nascita') ?>>
                <?= $messaggioErrore('data_nascita') ?>
            </div>

            <?= $tendina('area_geografica', 'area') ?>
            <?= $tendina('occupazione', 'occupazione') ?>
        </div>
    </fieldset>

    <div class="azioni-form">
        <button type="submit" class="btn btn-primario"><?= $inModifica ? 'Salva modifiche' : 'Crea utente' ?></button>
        <a class="btn" href="<?= e(url('utenti')) ?>">Annulla</a>
    </div>
</form>
