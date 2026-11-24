// ---------------------------------------------------------------
// Configurazione dell'APP MOBILE.
// Lo script scripts/sincronizza.js copia questo file in www/js/config.js
// al posto di quello del portale web: e' l'unica differenza tra i due.
// ---------------------------------------------------------------
const CONFIG = {
    // 10.0.2.2 e' l'indirizzo con cui l'EMULATORE Android vede il computer su cui gira
    // ("localhost" dentro l'emulatore sarebbe il telefono finto, non il Mac).
    // Su un telefono vero va messo l'indirizzo del computer nella rete wifi,
    // es. http://192.168.1.20:8080/api
    API_BASE: 'http://10.0.2.2:8080/api',
    // le compilazioni inviate dall'app vengono registrate come canale MOBILE
    CANALE: 'MOBILE'
};
