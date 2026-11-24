#!/usr/bin/env node
// =====================================================================
// Costruisce la cartella www/ dell'app partendo dal portale web.
//
// L'app e il portale sono LO STESSO codice: questo script copia
//   ../client   -> www/          (pagine, css e javascript)
//   ../stile    -> www/stile/    (tema grafico e animazioni)
// e fa le tre piccole modifiche che servono dentro Cordova:
//   1. usa config.mobile.js al posto di js/config.js (indirizzo API e canale)
//   2. corregge i percorsi "../stile/" in "stile/"
//   3. aggiunge cordova.js e autorizza l'indirizzo delle API nella
//      Content-Security-Policy
//
// Parte da solo prima di ogni "cordova prepare / build / run" (vedi <hook>
// in config.xml). Si puo anche lanciare a mano:  node scripts/sincronizza.js
// =====================================================================
const fs = require('fs');
const path = require('path');

// Copia una cartella con tutto il contenuto, file per file.
// (Scritta a mano invece di usare fs.cpSync: e' piu semplice da leggere e
// funziona anche su dischi che non permettono di copiare i permessi.)
function copiaCartella(da, a) {
    fs.mkdirSync(a, { recursive: true });
    fs.readdirSync(da, { withFileTypes: true }).forEach(function (voce) {
        if (voce.name === '.DS_Store') return; // file nascosto del Mac, non serve
        const sorgente = path.join(da, voce.name);
        const destinazione = path.join(a, voce.name);
        if (voce.isDirectory()) {
            copiaCartella(sorgente, destinazione);
        } else {
            fs.copyFileSync(sorgente, destinazione);
        }
    });
}

function sincronizza() {
    const radiceApp = path.resolve(__dirname, '..');
    const client = path.resolve(radiceApp, '..', 'client');
    const stile = path.resolve(radiceApp, '..', 'stile');
    const www = path.join(radiceApp, 'www');

    if (!fs.existsSync(client) || !fs.existsSync(stile)) {
        throw new Error('Non trovo le cartelle client/ e stile/ accanto a mobile/');
    }

    // leggo l'indirizzo delle API dalla configurazione mobile (es. http://10.0.2.2:8080)
    const configMobile = fs.readFileSync(path.join(radiceApp, 'config.mobile.js'), 'utf8');
    const trovato = configMobile.match(/API_BASE:\s*'(https?:\/\/[^/']+)/);
    if (!trovato) {
        throw new Error('In config.mobile.js non trovo API_BASE');
    }
    const origineApi = trovato[1];

    // riparto da una www pulita, cosi non restano file vecchi
    fs.rmSync(www, { recursive: true, force: true });
    fs.mkdirSync(www, { recursive: true });
    copiaCartella(client, www);
    copiaCartella(stile, path.join(www, 'stile'));
    fs.copyFileSync(path.join(radiceApp, 'config.mobile.js'), path.join(www, 'js', 'config.js'));

    // ritocco ogni pagina html
    let pagine = 0;
    fs.readdirSync(www).filter(function (nome) { return nome.endsWith('.html'); }).forEach(function (nome) {
        const file = path.join(www, nome);
        let html = fs.readFileSync(file, 'utf8');
        html = html.split('../stile/').join('stile/');
        html = html.replace('<!--CORDOVA-->', '<script src="cordova.js"></script>');
        html = html.replace("connect-src 'self' http://localhost:8080", "connect-src 'self' " + origineApi);
        // https://ssl.gstatic.com serve ad Android per la lettura dello schermo (TalkBack)
        html = html.replace("default-src 'self';", "default-src 'self' data: https://ssl.gstatic.com;");
        fs.writeFileSync(file, html);
        pagine++;
    });

    console.log('[sincronizza] www/ ricostruita da client/ e stile/: ' + pagine + ' pagine, API su ' + origineApi);
}

// Cordova chiama la funzione esportata; da riga di comando la lancio direttamente
module.exports = function () { sincronizza(); };
if (require.main === module) {
    sincronizza();
}
