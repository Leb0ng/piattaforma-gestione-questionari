// =====================================================================
// Script del solo Back Office.
// Le animazioni comuni stanno in ../stile/js/animazioni.js
// =====================================================================
(function () {
  'use strict';

  // ------------------------------------------------------------------
  // FILTRO DEI QUESTIONARI PER STATO
  // I pulsanti "Tutti / Bozza / Pubblicato / Chiuso" nascondono o mostrano
  // le righe della tabella leggendo l'attributo data-stato di ogni riga.
  // ------------------------------------------------------------------
  const corpo = document.querySelector('[data-righe-filtrabili]');
  if (corpo) {
    const pulsanti = document.querySelectorAll('.filtro');
    const nessuna = document.querySelector('[data-nessuna-riga]');

    pulsanti.forEach(function (pulsante) {
      pulsante.addEventListener('click', function () {
        const stato = pulsante.getAttribute('data-filtro');

        pulsanti.forEach(function (p) { p.setAttribute('aria-pressed', p === pulsante ? 'true' : 'false'); });

        let visibili = 0;
        corpo.querySelectorAll('tr').forEach(function (riga) {
          const mostra = stato === '' || riga.getAttribute('data-stato') === stato;
          riga.classList.toggle('nascosta', !mostra);
          riga.classList.remove('entra');
          if (mostra) {
            void riga.offsetWidth; // "risveglia" il browser cosi l'animazione riparte ogni volta
            riga.classList.add('entra');
            riga.style.animationDelay = (visibili * 40) + 'ms';
            visibili++;
          }
        });

        if (nessuna) nessuna.hidden = visibili > 0;
      });
    });
  }

  // ------------------------------------------------------------------
  // FORM DELLA DOMANDA
  // - le opzioni compaiono solo se la tipologia e' a scelta
  // - si possono aggiungere e togliere righe di opzioni
  // I controlli veri restano sul server: questo serve solo alla comodita.
  // ------------------------------------------------------------------
  const form = document.querySelector('[data-form-domanda]');
  if (form) {
    const blocco = form.querySelector('[data-blocco-opzioni]');
    const elenco = form.querySelector('[data-righe-opzioni]');

    function aggiornaBlocco() {
      const scelta = form.querySelector('input[name="tipologia"]:checked');
      blocco.hidden = !scelta || scelta.value === 'APERTA';
    }

    function rinumera() {
      elenco.querySelectorAll('.riga-opzione input').forEach(function (campo, i) {
        campo.placeholder = 'Opzione ' + (i + 1);
        campo.setAttribute('aria-label', 'Opzione ' + (i + 1));
      });
    }

    form.querySelectorAll('input[name="tipologia"]').forEach(function (radio) {
      radio.addEventListener('change', aggiornaBlocco);
    });

    form.querySelector('[data-aggiungi-opzione]').addEventListener('click', function () {
      // copio la prima riga e la svuoto: cosi l'HTML di una riga e' scritto in un posto solo
      const nuova = elenco.querySelector('.riga-opzione').cloneNode(true);
      nuova.querySelector('input').value = '';
      nuova.classList.remove('esce');
      nuova.classList.add('entra');
      elenco.appendChild(nuova);
      rinumera();
      nuova.querySelector('input').focus();
    });

    // un solo ascoltatore sull'elenco gestisce anche le righe aggiunte dopo
    elenco.addEventListener('click', function (evento) {
      const pulsante = evento.target.closest('[data-togli-opzione]');
      if (!pulsante) return;
      const riga = pulsante.closest('.riga-opzione');

      // ne restano sempre almeno due: sotto quella soglia svuoto il campo invece di toglierlo
      if (elenco.querySelectorAll('.riga-opzione').length <= 2) {
        riga.querySelector('input').value = '';
        riga.querySelector('input').focus();
        return;
      }
      riga.classList.add('esce');
      riga.addEventListener('animationend', function () { riga.remove(); rinumera(); }, { once: true });
    });

    aggiornaBlocco();
  }
})();
