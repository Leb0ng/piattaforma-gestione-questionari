// ---------------------------------------------------------------
// Tutte le chiamate ai servizi REST passano da qui.
// Portale web e app mobile usano questo stesso file: e' cosi che
// rispettano la richiesta di "servizi REST comuni".
// ---------------------------------------------------------------
const Api = {

    // ----- sessione: il token ricevuto al login resta nel browser -----
    token: function () { return localStorage.getItem('questionari_token'); },

    utente: function () {
        try { return JSON.parse(localStorage.getItem('questionari_utente')); }
        catch (e) { return null; }
    },

    ricorda: function (token, utente) {
        localStorage.setItem('questionari_token', token);
        localStorage.setItem('questionari_utente', JSON.stringify(utente));
    },

    // cambia solo alcuni dati dell'utente salvato (es. profiloCompleto dopo il salvataggio del profilo)
    aggiornaUtente: function (modifiche) {
        localStorage.setItem('questionari_utente', JSON.stringify(Object.assign({}, Api.utente(), modifiche)));
    },

    dimentica: function () {
        localStorage.removeItem('questionari_token');
        localStorage.removeItem('questionari_utente');
    },

    // ----- chiamata generica -----
    // Aggiunge da sola il token nell'intestazione Authorization.
    // Se va tutto bene restituisce i dati; altrimenti lancia un errore con
    // il messaggio preparato dal server (campo "errore") e il codice HTTP.
    chiama: async function (metodo, percorso, corpo) {
        const intestazioni = { 'Content-Type': 'application/json' };
        if (Api.token()) intestazioni['Authorization'] = 'Bearer ' + Api.token();

        let risposta;
        try {
            risposta = await fetch(CONFIG.API_BASE + percorso, {
                method: metodo,
                headers: intestazioni,
                body: corpo ? JSON.stringify(corpo) : undefined
            });
        } catch (e) {
            // fetch fallisce cosi solo se il server non e' raggiungibile
            const errore = new Error('Non riesco a raggiungere il server. Controlla la connessione e riprova.');
            errore.stato = 0;
            throw errore;
        }

        // 204 = "fatto, nessun contenuto" (es. logout)
        const dati = risposta.status === 204 ? null : await risposta.json().catch(function () { return null; });

        if (!risposta.ok) {
            // 401 fuori dal login = token scaduto o non valido: si torna alla pagina di accesso
            if (risposta.status === 401 && percorso !== '/auth/login') {
                Api.dimentica();
                window.location.replace('index.html');
            }
            const errore = new Error((dati && dati.errore) || 'Si è verificato un errore. Riprova.');
            errore.stato = risposta.status;
            throw errore;
        }
        return dati;
    },

    // ----- le operazioni disponibili -----
    login: function (username, password) {
        return Api.chiama('POST', '/auth/login', { username: username, password: password });
    },
    logout: function () {
        return Api.chiama('POST', '/auth/logout');
    },
    questionari: function () {
        return Api.chiama('GET', '/questionari');
    },
    questionario: function (id) {
        return Api.chiama('GET', '/questionari/' + encodeURIComponent(id));
    },
    inviaCompilazione: function (id, risposte) {
        return Api.chiama('POST', '/questionari/' + encodeURIComponent(id) + '/compilazioni',
            { canale: CONFIG.CANALE, risposte: risposte });
    },
    compilazioni: function () {
        return Api.chiama('GET', '/compilazioni');
    },
    compilazione: function (id) {
        return Api.chiama('GET', '/compilazioni/' + encodeURIComponent(id));
    },
    profilo: function () {
        return Api.chiama('GET', '/profilo');
    },
    salvaProfilo: function (profilo) {
        return Api.chiama('PUT', '/profilo', profilo);
    }
};
