// Elenco delle compilazioni dell'utente
(async function () {
    if (!Layout.monta('storico')) return;
    const elenco = document.getElementById('elenco');

    let compilazioni;
    try {
        compilazioni = await Api.compilazioni();
    } catch (errore) {
        riempi(elenco, crea('p', { class: 'avviso avviso-errore', role: 'alert', text: errore.message }));
        return;
    }

    if (compilazioni.length === 0) {
        riempi(elenco, crea('section', { class: 'scheda vuoto', 'data-anima': '' },
            crea('h2', { text: 'Ancora nessuna risposta' }),
            crea('p', { class: 'tenue', text: 'Quando compili un questionario, le risposte che hai inviato restano qui.' }),
            crea('p', {}, crea('a', { class: 'btn btn-primario', href: 'questionari.html', text: 'Vai ai questionari' }))));
        return;
    }

    const lista = crea('div', { class: 'schede', 'data-anima-gruppo': '' });
    compilazioni.forEach(function (c) {
        lista.appendChild(crea('article', { class: 'scheda q-scheda', 'data-luce': '', 'data-anima': '' },
            crea('div', { class: 'q-testo' },
                crea('p', { class: 'domanda-meta' },
                    badgeStato(c.statoQuestionario),
                    crea('span', { class: 'badge badge-neutro', text: etichettaCanale(c.canale) })),
                crea('h2', { text: c.titolo }),
                crea('p', { class: 'piccolo tenue', text: 'Inviato il ' + formattaData(c.inviataIl) })),
            crea('a', { class: 'btn', href: 'risposte.html?id=' + encodeURIComponent(c.id), text: 'Leggi le risposte' })));
    });
    riempi(elenco, lista);
})();
