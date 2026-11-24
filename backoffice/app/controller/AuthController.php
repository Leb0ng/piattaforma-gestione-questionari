<?php
declare(strict_types=1);

final class AuthController
{
    public function login(): void
    {
        if (adminLoggato()) {
            vai('dashboard');
        }

        $errore = null;
        $username = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            richiediPost();
            $username = testoDa($_POST, 'username');
            $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

            $utente = $username !== '' ? UtenteModel::trovaPerUsername($username) : null;

            // password_verify confronta la password con l'hash bcrypt salvato nel db
            if ($utente === null || !password_verify($password, $utente['password_hash'])) {
                usleep(400000); // piccola pausa: rallenta chi prova password a raffica
                $errore = 'Username o password non corretti.';
            } elseif ($utente['ruolo'] !== 'ADMIN') {
                $errore = 'Questo accesso è riservato all\'amministratore. Gli utenti compilano i questionari dal portale o dall\'app.';
            } else {
                // cambio l'id di sessione al momento dell'accesso (contro il "session fixation")
                session_regenerate_id(true);
                unset($_SESSION['csrf']);
                $_SESSION['admin'] = [
                    'id'       => (int) $utente['id'],
                    'nome'     => $utente['nome'],
                    'username' => $utente['username'],
                ];
                vai('dashboard');
            }
        }

        vista('auth/login', ['errore' => $errore, 'username' => $username], 'Accesso');
    }

    public function logout(): void
    {
        richiediPost();
        $_SESSION = [];
        session_destroy();
        vai('login');
    }
}
