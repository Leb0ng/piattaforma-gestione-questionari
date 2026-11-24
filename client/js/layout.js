// ---------------------------------------------------------------
// Parti comuni delle pagine interne: testata con menu e piede.
// Sono scritte qui UNA volta e montate in ogni pagina: e' il
// "riutilizzo delle parti comuni" richiesto dalla traccia.
// ---------------------------------------------------------------
const Layout = {

    // paginaCorrente: 'questionari', 'storico' o 'profilo' (serve a evidenziare la voce del menu)
    monta: function (paginaCorrente) {
        // senza token non si entra: si torna all'accesso
        if (!Api.token()) {
            window.location.replace('index.html');
            return false;
        }
        const utente = Api.utente() || {};

        const marchio = crea('a', { class: 'marchio', href: 'questionari.html' });
        // il segno del marchio e' un disegno fisso, senza dati esterni: qui innerHTML e' sicuro
        marchio.innerHTML =
            '<svg class="marchio-segno" viewBox="0 0 28 28" width="28" height="28" aria-hidden="true">' +
            '<circle cx="7" cy="7" r="4.25" fill="none" stroke="currentColor" stroke-width="1.5"/>' +
            '<circle cx="7" cy="21" r="4.25" fill="none" stroke="currentColor" stroke-width="1.5"/>' +
            '<circle cx="21" cy="14" r="4.25" fill="none" stroke="currentColor" stroke-width="1.5"/>' +
            '<circle class="marchio-pieno" cx="21" cy="14" r="2.1"/></svg>';
        marchio.appendChild(crea('span', { text: 'Questionari' }));

        const voce = function (pagina, indirizzo, testo) {
            return crea('a', { href: indirizzo, 'aria-current': pagina === paginaCorrente ? 'page' : null, text: testo });
        };
        const menu = crea('nav', { class: 'p-menu', 'aria-label': 'Menu principale' },
            voce('questionari', 'questionari.html', 'Da compilare'),
            voce('storico', 'storico.html', 'Le mie risposte'),
            voce('profilo', 'profilo.html', 'Profilo'));

        const esci = crea('button', { type: 'button', class: 'btn btn-piccolo', text: 'Esci' });
        esci.addEventListener('click', Layout.esci);

        const chi = crea('div', { class: 'p-utente' },
            crea('span', { class: 'piccolo tenue p-nome', text: utente.nome || '' }),
            esci);

        document.getElementById('testata').replaceChildren(marchio, menu, chi);
        document.getElementById('piede').textContent = 'Questionari. Project Work di Leon Sciurti.';
        return true;
    },

    esci: async function () {
        // avviso il server (cancella il token dal database); se non risponde esco comunque
        try { await Api.logout(); } catch (e) { /* niente: il token locale lo tolgo lo stesso */ }
        Api.dimentica();
        window.location.replace('index.html');
    }
};
