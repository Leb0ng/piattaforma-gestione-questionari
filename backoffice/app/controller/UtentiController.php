<?php
declare(strict_types=1);

// Gestione degli utenti dal Back Office: elenco, creazione e modifica del profilo.
// Il profilo (sesso, data di nascita, area, occupazione) decide quali questionari
// l'utente vede. Lo puo completare o cambiare anche l'utente stesso dal portale/app.
// Gli utenti non si eliminano: le loro compilazioni devono restare (FK RESTRICT).
final class UtentiController
{
    private const USERNAME_MAX = 50;
    private const NOME_MAX = 100;
    private const PASSWORD_MIN = 8;
    // stessi limiti delle API (ProfiloService)
    private const ETA_MINIMA = 14;
    private const ETA_MASSIMA = 120;

    public function elenco(): void
    {
        vista('utenti/elenco', ['utenti' => UtenteModel::tutti()], 'Utenti');
    }

    public function nuovo(): void
    {
        $dati = ['username' => '', 'nome' => '', 'sesso' => '', 'data_nascita' => '', 'area_geografica' => '', 'occupazione' => ''];
        $errori = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            richiediPost();
            [$dati, $errori] = $this->leggiForm(true);
            if (!$errori) {
                $id = UtenteModel::crea($dati['username'], $dati['nome'], password_hash($dati['password'], PASSWORD_DEFAULT), $this->profilo($dati));
                if ($id > 0) {
                    flash('ok', 'Utente creato. Può accedere al portale e all\'app con lo username "' . $dati['username'] . '".');
                    vai('utenti');
                }
                $errori['username'] = 'Questo username è già usato.';
            }
            unset($dati['password']); // la password non torna mai nella pagina
        }

        vista('utenti/form', ['dati' => $dati, 'errori' => $errori, 'utente' => null], 'Nuovo utente');
    }

    public function modifica(): void
    {
        $utente = UtenteModel::trova(idDa($_GET, 'id'));
        if ($utente === null) {
            nonTrovato('Questo utente non esiste.');
        }
        $id = (int) $utente['id'];

        $dati = [
            'username'        => $utente['username'],
            'nome'            => $utente['nome'],
            'sesso'           => $utente['sesso'] ?? '',
            'data_nascita'    => $utente['data_nascita'] ?? '',
            'area_geografica' => $utente['area_geografica'] ?? '',
            'occupazione'     => $utente['occupazione'] ?? '',
        ];
        $errori = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            richiediPost();
            [$dati, $errori] = $this->leggiForm(false);
            $dati['username'] = $utente['username']; // lo username non si cambia
            if (!$errori) {
                $hash = $dati['password'] !== '' ? password_hash($dati['password'], PASSWORD_DEFAULT) : null;
                UtenteModel::aggiorna($id, $dati['nome'], $this->profilo($dati), $hash);
                flash('ok', 'Utente aggiornato.');
                vai('utenti');
            }
            unset($dati['password']);
        }

        vista('utenti/form', ['dati' => $dati, 'errori' => $errori, 'utente' => $utente], 'Modifica utente');
    }

    // ---------- di servizio ----------

    // i 4 campi del profilo pronti per il modello ('' -> null = non indicato)
    private function profilo(array $dati): array
    {
        $profilo = [];
        foreach (['sesso', 'data_nascita', 'area_geografica', 'occupazione'] as $campo) {
            $profilo[$campo] = $dati[$campo] !== '' ? $dati[$campo] : null;
        }
        return $profilo;
    }

    // Legge e controlla il form. In creazione username e password sono obbligatori,
    // in modifica la password si scrive solo per cambiarla. Restituisce [dati, errori].
    private function leggiForm(bool $nuovo): array
    {
        $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
        $dati = [
            'username'        => testoDa($_POST, 'username'),
            'nome'            => testoDa($_POST, 'nome'),
            'password'        => $password,
            'sesso'           => testoDa($_POST, 'sesso'),
            'data_nascita'    => testoDa($_POST, 'data_nascita'),
            'area_geografica' => testoDa($_POST, 'area_geografica'),
            'occupazione'     => testoDa($_POST, 'occupazione'),
        ];
        $errori = [];

        if ($nuovo) {
            if ($dati['username'] === '') {
                $errori['username'] = 'Scrivi uno username.';
            } elseif (!preg_match('/^[A-Za-z0-9._-]{3,' . self::USERNAME_MAX . '}$/', $dati['username'])) {
                $errori['username'] = 'Da 3 a ' . self::USERNAME_MAX . ' caratteri: lettere, numeri, punto, trattino e trattino basso.';
            } elseif (UtenteModel::usernameEsiste($dati['username'])) {
                $errori['username'] = 'Questo username è già usato.';
            }
        }

        if ($dati['nome'] === '') {
            $errori['nome'] = 'Scrivi nome e cognome.';
        } elseif (mb_strlen($dati['nome']) > self::NOME_MAX) {
            $errori['nome'] = 'Il nome può avere al massimo ' . self::NOME_MAX . ' caratteri.';
        }

        if ($nuovo || $password !== '') {
            if (mb_strlen($password) < self::PASSWORD_MIN) {
                $errori['password'] = 'La password deve avere almeno ' . self::PASSWORD_MIN . ' caratteri.';
            } elseif (strlen($password) > 72) {
                $errori['password'] = 'La password può avere al massimo 72 caratteri.'; // limite di bcrypt
            }
        }

        // profilo: ogni campo si puo lasciare vuoto (lo completera l'utente),
        // ma se c'e' deve essere un valore previsto
        $categorie = categorie();
        foreach (['sesso' => 'sesso', 'area_geografica' => 'area', 'occupazione' => 'occupazione'] as $campo => $categoria) {
            if ($dati[$campo] !== '' && !isset($categorie[$categoria]['valori'][$dati[$campo]])) {
                $errori[$campo] = 'Scegli un valore dall\'elenco.';
                $dati[$campo] = '';
            }
        }

        if ($dati['data_nascita'] !== '') {
            $data = DateTimeImmutable::createFromFormat('!Y-m-d', $dati['data_nascita']);
            if ($data === false || $data->format('Y-m-d') !== $dati['data_nascita']) {
                $errori['data_nascita'] = 'Data non valida.';
            } else {
                $anni = eta($dati['data_nascita']);
                if ($data > new DateTimeImmutable('today') || $anni < self::ETA_MINIMA || $anni > self::ETA_MASSIMA) {
                    $errori['data_nascita'] = 'L\'età deve essere tra ' . self::ETA_MINIMA . ' e ' . self::ETA_MASSIMA . ' anni.';
                }
            }
        }

        return [$dati, $errori];
    }
}
