// Una compilazione gia inviata, in sola lettura
(async function () {
    if (!Layout.monta('storico')) return;

    let c;
    try {
        const id = parametro('id');
        if (!id) throw new Error('Compilazione non indicata.');
        // se l'id e' di un altro utente il server risponde "non trovata"
        c = await Api.compilazione(id);
    } catch (errore) {
        document.getElementById('titolo').textContent = 'Risposte non disponibili';
        mostraErrore(errore.message);
        return;
    }

    document.title = c.titolo + ' | Questionari';
    document.getElementById('etichette').replaceChildren(
        badgeStato(c.statoQuestionario),
        crea('span', { class: 'badge badge-neutro', text: etichettaCanale(c.canale) }));
    const titolo = document.getElementById('titolo');
    titolo.textContent = c.titolo;
    titolo.setAttribute('data-titolo', '');
    document.getElementById('descrizione').textContent = 'Inviato il ' + formattaData(c.inviataIl) + '.';
    document.getElementById('sola-lettura').hidden = false;

    const lista = document.getElementById('domande');
    c.risposte.forEach(function (r, indice) {
        let contenuto;
        const scelte = r.opzioni.filter(function (o) { return o.scelta; });

        if (r.tipologia === 'APERTA') {
            contenuto = r.testo
                ? crea('blockquote', { class: 'risposta-testo', text: r.testo })
                : crea('p', { class: 'tenue', text: 'Nessuna risposta.' });
        } else if (scelte.length === 0) {
            contenuto = crea('p', { class: 'tenue', text: 'Nessuna risposta.' });
        } else {
            // mostro tutte le opzioni e segno quelle scelte
            contenuto = crea('ul', { class: 'opzioni opzioni-' + r.tipologia.toLowerCase() });
            r.opzioni.forEach(function (o) {
                contenuto.appendChild(crea('li', { class: o.scelta ? 'scelta' : 'non-scelta' },
                    o.testo,
                    o.scelta ? crea('span', { class: 'solo-lettori', text: ' (scelta)' }) : null));
            });
        }

        lista.appendChild(crea('li', { class: 'scheda domanda', 'data-anima': '' },
            crea('div', { class: 'domanda-testa' },
                crea('span', { class: 'domanda-numero numeri', 'aria-hidden': 'true', text: String(indice + 1) }),
                crea('div', { class: 'domanda-corpo' },
                    crea('h3', { text: r.domanda }),
                    crea('p', { class: 'domanda-meta' },
                        crea('span', { class: 'badge badge-neutro', text: etichettaTipologia(r.tipologia) }),
                        crea('span', { class: 'badge badge-neutro', text: r.obbligatoria ? 'Obbligatoria' : 'Facoltativa' })),
                    contenuto))));
    });

    if (window.Animazioni) window.Animazioni.aggiorna(document);
})();
