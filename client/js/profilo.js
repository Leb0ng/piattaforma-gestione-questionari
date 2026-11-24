// Profilo dell'utente: sesso, data di nascita, area geografica, condizione lavorativa.
// Sono i dati con cui il server decide quali questionari mostrare.
(async function () {
    if (!Layout.monta('profilo')) return;

    const form = document.getElementById('form-profilo');
    const pulsante = document.getElementById('btn-salva');
    const salvato = document.getElementById('salvato');
    // ?primo=1 -> arrivo qui subito dopo il login perche il profilo e' incompleto
    const primoAccesso = parametro('primo') === '1';

    // la data di nascita non puo essere nel futuro
    const oggi = new Date();
    form.elements.dataNascita.max = oggi.getFullYear() + '-' +
        String(oggi.getMonth() + 1).padStart(2, '0') + '-' + String(oggi.getDate()).padStart(2, '0');

    // ---------- 1) carico il profilo attuale ----------
    let profilo;
    try {
        profilo = await Api.profilo();
    } catch (errore) {
        mostraErrore(errore.message);
        return;
    }

    document.getElementById('chi').textContent = profilo.nome + ' · ' + profilo.username;
    form.elements.sesso.value = profilo.sesso || '';
    form.elements.dataNascita.value = profilo.dataNascita || '';
    form.elements.areaGeografica.value = profilo.areaGeografica || '';
    form.elements.occupazione.value = profilo.occupazione || '';
    document.getElementById('benvenuto').hidden = !(primoAccesso && !profilo.completo);
    form.hidden = false;
    if (window.Animazioni) window.Animazioni.aggiorna(document);

    // appena l'utente cambia qualcosa, tolgo il messaggio "salvato" e la segnalazione rossa
    form.addEventListener('input', function (evento) {
        salvato.hidden = true;
        evento.target.removeAttribute('aria-invalid');
    });

    // ---------- 2) salvataggio ----------
    form.addEventListener('submit', async function (evento) {
        evento.preventDefault();
        nascondiErrore();
        salvato.hidden = true;

        // controllo di comodita nel browser: tutti i campi sono obbligatori
        // (quello vero lo rifa il server)
        const campi = ['sesso', 'dataNascita', 'areaGeografica', 'occupazione'];
        const mancanti = campi.filter(function (nome) { return form.elements[nome].value === ''; });
        campi.forEach(function (nome) {
            if (mancanti.includes(nome)) form.elements[nome].setAttribute('aria-invalid', 'true');
        });
        if (mancanti.length) {
            mostraErrore('Compila tutti i campi del profilo.');
            form.elements[mancanti[0]].focus();
            return;
        }

        pulsante.disabled = true;
        pulsante.textContent = 'Salvataggio…';
        try {
            const aggiornato = await Api.salvaProfilo({
                sesso: form.elements.sesso.value,
                dataNascita: form.elements.dataNascita.value,
                areaGeografica: form.elements.areaGeografica.value,
                occupazione: form.elements.occupazione.value
            });
            // aggiorno anche l'utente salvato nel browser, cosi l'avviso "profilo incompleto" sparisce
            Api.aggiornaUtente({ profiloCompleto: aggiornato.completo });

            if (primoAccesso) {
                window.location.replace('questionari.html');
                return;
            }
            salvato.hidden = false;
        } catch (errore) {
            mostraErrore(errore.message);
        }
        pulsante.disabled = false;
        pulsante.textContent = 'Salva il profilo';
    });
})();
