// Compilazione di un questionario
(async function () {
    if (!Layout.monta('questionari')) return;

    const id = parametro('id');
    const form = document.getElementById('form-compila');
    const lista = document.getElementById('domande');
    const pulsante = document.getElementById('btn-invia');
    let domande = [];

    // ---------- 1) carico il questionario ----------
    let questionario;
    try {
        if (!id) throw new Error('Questionario non indicato.');
        questionario = await Api.questionario(id);
    } catch (errore) {
        // 404 = non esiste (o e' una bozza), 409 = chiuso: il messaggio lo scrive il server
        document.getElementById('titolo').textContent = 'Questionario non disponibile';
        mostraErrore(errore.message);
        return;
    }

    domande = questionario.domande;
    document.title = questionario.titolo + ' | Questionari';
    const titolo = document.getElementById('titolo');
    titolo.textContent = questionario.titolo;
    titolo.setAttribute('data-titolo', '');
    document.getElementById('descrizione').textContent = questionario.descrizione;

    // ---------- 2) costruisco i campi, uno per domanda ----------
    domande.forEach(function (domanda, indice) {
        const nome = 'domanda-' + domanda.id;
        let campo;

        if (domanda.tipologia === 'APERTA') {
            campo = crea('textarea', {
                class: 'input', id: nome, name: nome, rows: '4', maxlength: '5000',
                'aria-labelledby': nome + '-testo', placeholder: 'Scrivi qui la tua risposta'
            });
        } else {
            // SINGOLA = radio (se ne sceglie una), MULTIPLA = checkbox (una o piu)
            const tipo = domanda.tipologia === 'SINGOLA' ? 'radio' : 'checkbox';
            campo = crea('div', { class: 'scelte', role: tipo === 'radio' ? 'radiogroup' : 'group', 'aria-labelledby': nome + '-testo' });
            domanda.opzioni.forEach(function (opzione) {
                campo.appendChild(crea('label', { class: 'voce-scelta' },
                    crea('input', { type: tipo, name: nome, value: String(opzione.id) }),
                    crea('i', { 'aria-hidden': 'true' }),
                    crea('span', { text: opzione.testo })));
            });
        }

        lista.appendChild(crea('li', { class: 'scheda domanda domanda-form', 'data-anima': '', 'data-domanda': String(domanda.id) },
            crea('div', { class: 'domanda-testa' },
                crea('span', { class: 'domanda-numero numeri', 'aria-hidden': 'true', text: String(indice + 1) }),
                crea('div', { class: 'domanda-corpo' },
                    crea('h3', { id: nome + '-testo', text: domanda.testo }),
                    crea('p', { class: 'domanda-meta' },
                        crea('span', { class: 'badge badge-neutro', text: etichettaTipologia(domanda.tipologia) }),
                        crea('span', { class: 'badge ' + (domanda.obbligatoria ? 'badge-bozza' : 'badge-neutro'),
                            text: domanda.obbligatoria ? 'Obbligatoria' : 'Facoltativa' })),
                    campo,
                    crea('p', { class: 'errore-campo', hidden: true, text: 'Questa domanda è obbligatoria.' })))));
    });

    form.hidden = false;
    document.getElementById('avanzamento').hidden = false;
    if (window.Animazioni) window.Animazioni.aggiorna(document);

    // ---------- 3) leggo le risposte dal form ----------
    // Restituisce la risposta a una domanda nel formato che si aspettano le API,
    // oppure null se l'utente non ha risposto.
    function leggi(domanda) {
        const nome = 'domanda-' + domanda.id;
        if (domanda.tipologia === 'APERTA') {
            const testo = form.elements[nome].value.trim();
            return testo === '' ? null : { domandaId: domanda.id, testo: testo };
        }
        const scelte = Array.from(form.querySelectorAll('input[name="' + nome + '"]:checked'))
            .map(function (input) { return Number(input.value); });
        return scelte.length === 0 ? null : { domandaId: domanda.id, opzioniIds: scelte };
    }

    // barra di avanzamento: quante domande hanno una risposta
    function aggiornaAvanzamento() {
        const risposte = domande.filter(function (d) { return leggi(d) !== null; }).length;
        const quota = domande.length ? risposte / domande.length : 0;
        document.getElementById('avanzamento-pieno').style.transform = 'scaleX(' + quota + ')';
        document.getElementById('avanzamento-testo').textContent = risposte + ' di ' + domande.length +
            (domande.length === 1 ? ' domanda con risposta' : ' domande con risposta');
    }
    form.addEventListener('input', function (evento) {
        aggiornaAvanzamento();
        // appena l'utente risponde a una domanda segnata in rosso, tolgo la segnalazione
        const riga = evento.target.closest('[data-domanda]');
        if (riga) segnala(riga, false);
    });
    aggiornaAvanzamento();

    function segnala(riga, errore) {
        riga.classList.toggle('con-errore', errore);
        riga.querySelector('.errore-campo').hidden = !errore;
    }

    // ---------- 4) invio ----------
    // Controllo di comodita nel browser sulle domande obbligatorie
    // (quello vero lo rifa comunque il server). Restituisce true se e' tutto a posto.
    function controllaObbligatorie() {
        let primaMancante = null;
        domande.forEach(function (domanda) {
            const riga = lista.querySelector('[data-domanda="' + domanda.id + '"]');
            const manca = domanda.obbligatoria && leggi(domanda) === null;
            segnala(riga, manca);
            if (manca && !primaMancante) primaMancante = riga;
        });
        if (primaMancante) primaMancante.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return primaMancante === null;
    }

    // animazioni.js lancia "conferma:prima" subito prima di aprire la finestra di conferma:
    // se manca una risposta obbligatoria annullo l'evento e la finestra non compare
    form.addEventListener('conferma:prima', function (evento) {
        if (!controllaObbligatorie()) evento.preventDefault();
    });

    // Il submit arriva due volte: la prima apre la finestra di conferma, la seconda
    // (dopo il clic su "Invia", con data-confermato="si") e' quella che chiama le API.
    form.addEventListener('submit', async function (evento) {
        evento.preventDefault();
        nascondiErrore();

        const conFinestra = typeof HTMLDialogElement !== 'undefined';
        if (conFinestra && form.dataset.confermato !== 'si') return; // aspetto la conferma
        form.dataset.confermato = '';

        if (!controllaObbligatorie()) return;

        const risposte = domande.map(leggi).filter(function (r) { return r !== null; });

        pulsante.disabled = true;
        pulsante.textContent = 'Invio in corso…';
        try {
            const compilazione = await Api.inviaCompilazione(id, risposte);

            // conferma dell'avvenuto salvataggio (richiesta dalla traccia)
            form.hidden = true;
            document.getElementById('avanzamento').hidden = true;
            document.getElementById('fatto-testo').textContent =
                'Le tue risposte a "' + questionario.titolo + '" sono state registrate il ' +
                formattaData(compilazione.inviataIl) + '.';
            document.getElementById('fatto-rivedi').href = 'risposte.html?id=' + encodeURIComponent(compilazione.id);
            const fatto = document.getElementById('fatto');
            fatto.hidden = false;
            fatto.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } catch (errore) {
            // es. 409 "Hai già compilato questo questionario" oppure "è chiuso"
            mostraErrore(errore.message);
            pulsante.disabled = false;
            pulsante.textContent = 'Invia le risposte';
        }
    });
})();
