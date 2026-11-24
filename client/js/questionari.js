// Elenco dei questionari pubblicati
(async function () {
    if (!Layout.monta('questionari')) return;
    const elenco = document.getElementById('elenco');

    let questionari;
    try {
        questionari = await Api.questionari();
    } catch (errore) {
        riempi(elenco, crea('p', { class: 'avviso avviso-errore', role: 'alert', text: errore.message }));
        return;
    }

    // separo quelli ancora da fare da quelli gia compilati
    const daFare = questionari.filter(function (q) { return !q.giaCompilato; });
    const fatti = questionari.filter(function (q) { return q.giaCompilato; });

    const pezzi = [];

    // con il profilo incompleto alcuni questionari (quelli rivolti a certe categorie) non si vedono
    const utente = Api.utente() || {};
    if (utente.profiloCompleto === false) {
        pezzi.push(crea('p', { class: 'avviso avviso-info', 'data-anima': '' },
            crea('span', {},
                'Il tuo profilo è incompleto: alcuni questionari sono riservati a certe persone e non li vedrai finché non lo completi. ',
                crea('a', { href: 'profilo.html', text: 'Completa il profilo' }))));
    }

    if (daFare.length === 0) {
        pezzi.push(crea('section', { class: 'scheda vuoto', 'data-anima': '' },
            crea('h2', { text: fatti.length ? 'Hai compilato tutto' : 'Nessun questionario aperto' }),
            crea('p', { class: 'tenue', text: fatti.length
                ? 'Non ci sono altri questionari da compilare. Quando ne viene pubblicato uno nuovo lo trovi qui.'
                : 'In questo momento non ci sono questionari da compilare. Torna più tardi.' })));
    } else {
        const lista = crea('div', { class: 'schede', 'data-anima-gruppo': '' });
        daFare.forEach(function (q) {
            lista.appendChild(crea('article', { class: 'scheda q-scheda', 'data-luce': '', 'data-anima': '' },
                crea('div', { class: 'q-testo' },
                    crea('h2', { text: q.titolo }),
                    crea('p', { class: 'tenue q-descrizione', text: q.descrizione }),
                    crea('p', { class: 'piccolo tenue', text: 'Aperto dal ' + formattaData(q.pubblicatoIl, false) })),
                crea('a', { class: 'btn btn-primario', href: 'compila.html?id=' + encodeURIComponent(q.id), text: 'Compila' })));
        });
        pezzi.push(lista);
    }

    if (fatti.length) {
        const lista = crea('ul', { class: 'q-fatti' });
        fatti.forEach(function (q) {
            lista.appendChild(crea('li', {},
                crea('span', { text: q.titolo }),
                crea('span', { class: 'badge badge-neutro', text: 'Già compilato' })));
        });
        pezzi.push(crea('section', { class: 'pila', 'data-anima': '' },
            crea('h2', { text: 'Già compilati' }),
            lista,
            crea('p', {}, crea('a', { href: 'storico.html', text: 'Rileggi le tue risposte' }))));
    }

    riempi(elenco, ...pezzi);
})();
