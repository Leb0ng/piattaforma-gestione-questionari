// Pagina di accesso
(function () {
    // chi ha gia fatto l'accesso va direttamente ai questionari
    if (Api.token()) {
        window.location.replace('questionari.html');
        return;
    }

    const form = document.getElementById('form-login');
    const pulsante = document.getElementById('btn-accedi');

    form.addEventListener('submit', async function (evento) {
        evento.preventDefault(); // niente ricaricamento della pagina: invio io con fetch
        nascondiErrore();

        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value;
        if (username === '' || password === '') {
            mostraErrore('Inserisci username e password.');
            return;
        }

        pulsante.disabled = true;
        pulsante.textContent = 'Accesso in corso…';
        try {
            const dati = await Api.login(username, password);
            Api.ricorda(dati.token, dati.utente);
            // profilo incompleto: prima lo faccio completare, poi si va ai questionari
            window.location.replace(dati.utente.profiloCompleto ? 'questionari.html' : 'profilo.html?primo=1');
        } catch (errore) {
            // il messaggio arriva dal server: credenziali sbagliate, admin non ammesso...
            mostraErrore(errore.message);
            pulsante.disabled = false;
            pulsante.textContent = 'Accedi';
        }
    });
})();
