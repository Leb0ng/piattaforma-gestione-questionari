// ---------------------------------------------------------------
// Piccole funzioni usate da tutte le pagine
// ---------------------------------------------------------------

// Crea un elemento HTML: crea('p', { class: 'tenue', text: 'ciao' }, figlio1, figlio2...)
// Il testo viene sempre inserito con textContent, MAI con innerHTML:
// cosi quello che arriva dalle API non puo diventare codice nella pagina (XSS).
function crea(tag, attributi, ...figli) {
    const elemento = document.createElement(tag);
    Object.entries(attributi || {}).forEach(function ([nome, valore]) {
        if (valore === null || valore === undefined || valore === false) return;
        if (nome === 'text') elemento.textContent = valore;
        else if (nome === 'class') elemento.className = valore;
        else if (valore === true) elemento.setAttribute(nome, '');
        else elemento.setAttribute(nome, valore);
    });
    figli.forEach(function (figlio) {
        if (figlio === null || figlio === undefined || figlio === false) return;
        elemento.appendChild(typeof figlio === 'string' ? document.createTextNode(figlio) : figlio);
    });
    return elemento;
}

// Legge un parametro dall'indirizzo: compila.html?id=3 -> parametro('id') = "3"
function parametro(nome) {
    return new URLSearchParams(window.location.search).get(nome);
}

// "2026-11-11T10:42:29" -> "11 nov 2026, 10:42"
function formattaData(iso, conOra) {
    if (!iso) return '';
    const mesi = ['gen', 'feb', 'mar', 'apr', 'mag', 'giu', 'lug', 'ago', 'set', 'ott', 'nov', 'dic'];
    const d = new Date(iso);
    if (isNaN(d)) return '';
    let testo = d.getDate() + ' ' + mesi[d.getMonth()] + ' ' + d.getFullYear();
    if (conOra !== false) {
        testo += ', ' + String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0');
    }
    return testo;
}

function badgeStato(stato) {
    const etichette = { BOZZA: 'Bozza', PUBBLICATO: 'Aperto', CHIUSO: 'Chiuso' };
    return crea('span', { class: 'badge badge-' + String(stato).toLowerCase(), text: etichette[stato] || stato });
}

function etichettaTipologia(tipologia) {
    return { APERTA: 'Risposta aperta', SINGOLA: 'Una sola scelta', MULTIPLA: 'Una o più scelte' }[tipologia] || tipologia;
}

function etichettaCanale(canale) {
    return canale === 'MOBILE' ? 'Dall\'app' : 'Dal portale web';
}

// Mostra un messaggio di errore nel riquadro della pagina (e ci porta lo sguardo)
function mostraErrore(messaggio) {
    const riquadro = document.getElementById('errore');
    if (!riquadro) return;
    riquadro.textContent = messaggio;
    riquadro.hidden = false;
    riquadro.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function nascondiErrore() {
    const riquadro = document.getElementById('errore');
    if (riquadro) riquadro.hidden = true;
}

// Svuota un contenitore e ci mette i nuovi elementi, poi fa partire le animazioni di ingresso
function riempi(contenitore, ...elementi) {
    contenitore.replaceChildren(...elementi.filter(Boolean));
    if (window.Animazioni) window.Animazioni.aggiorna(contenitore);
}
