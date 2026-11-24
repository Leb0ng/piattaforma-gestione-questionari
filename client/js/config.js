// ---------------------------------------------------------------
// Configurazione del PORTALE WEB.
// L'app mobile usa gli stessi file ma con un config.js diverso
// (vedi mobile/config.mobile.js): cambiano indirizzo delle API e canale.
// ---------------------------------------------------------------
const CONFIG = {
    // dove rispondono i servizi REST in Java
    API_BASE: 'http://localhost:8080/api',
    // da quale canale partono le compilazioni: WEB oppure MOBILE
    CANALE: 'WEB'
};
