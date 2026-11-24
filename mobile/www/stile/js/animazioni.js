// =====================================================================
// ANIMAZIONI CONDIVISE (Back Office, portale e app)
// Tutto in JavaScript "puro", senza librerie.
// Ogni effetto si attiva mettendo un attributo data-... nell'HTML:
//   data-anima         -> l'elemento compare quando entra nello schermo
//   data-titolo        -> il titolo sale parola per parola
//   data-conta="42"    -> il numero conta da 0 a 42
//   data-luce          -> una luce segue il mouse sulla scheda
//   data-conferma="…"  -> prima di inviare il form chiede conferma
//   data-costellazione -> la costellazione dello sfondo (si accende col mouse, onda col clic)
// =====================================================================
(function () {
  'use strict';

  // segnalo al CSS che JavaScript funziona (vedi ".js [data-anima]" in stile.css)
  document.documentElement.classList.add('js');

  // se l'utente ha chiesto al sistema operativo di ridurre le animazioni, le salto
  const pocoMovimento = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // ------------------------------------------------------------------
  // 1) INGRESSO DEGLI ELEMENTI
  // IntersectionObserver avvisa quando un elemento entra nella parte
  // visibile della pagina: a quel punto gli aggiungo la classe "visibile".
  // Gli elementi dello stesso gruppo entrano in sequenza (ritardo a scalare).
  // Ogni funzione riceve una "radice": cosi posso richiamarle anche sui
  // pezzi di pagina creati dopo con JavaScript (vedi Animazioni.aggiorna).
  // ------------------------------------------------------------------
  let osservatore = null;
  if (!pocoMovimento && 'IntersectionObserver' in window) {
    osservatore = new IntersectionObserver(function (voci) {
      voci.forEach(function (voce) {
        if (voce.isIntersecting) {
          voce.target.classList.add('visibile');
          osservatore.unobserve(voce.target); // l'animazione avviene una volta sola
        }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -5% 0px' });
  }

  function preparaIngressi(radice) {
    // prendo solo gli elementi non ancora preparati (data-pronto li segna)
    const elementi = radice.querySelectorAll('[data-anima]:not([data-pronto]), [data-titolo]:not([data-pronto])');

    // ritardo a scalare: conto la posizione dell'elemento tra i "fratelli" animati
    const contatori = new Map();
    elementi.forEach(function (el) {
      el.setAttribute('data-pronto', '');

      if (!osservatore) { el.classList.add('visibile'); return; }

      const gruppo = el.closest('[data-anima-gruppo]') || el.parentElement;
      const indice = contatori.get(gruppo) || 0;
      contatori.set(gruppo, indice + 1);
      if (!el.style.getPropertyValue('--ritardo')) {
        el.style.setProperty('--ritardo', Math.min(indice * 70, 560) + 'ms');
      }
      osservatore.observe(el);
    });
  }

  // ------------------------------------------------------------------
  // 2) TITOLI PAROLA PER PAROLA
  // Spezzo il testo in parole e avvolgo ognuna in due <span>: quello
  // esterno fa da maschera, quello interno sale dal basso (vedi il CSS).
  // ------------------------------------------------------------------
  function preparaTitoli(radice) {
    radice.querySelectorAll('[data-titolo]:not([data-spezzato])').forEach(function (titolo) {
      titolo.setAttribute('data-spezzato', '');
      const testo = titolo.textContent.trim();
      if (testo === '') return;
      const parole = testo.split(/\s+/);
      titolo.setAttribute('aria-label', testo); // i lettori di schermo leggono il testo intero
      titolo.textContent = '';
      parole.forEach(function (parola, i) {
        const maschera = document.createElement('span');
        maschera.className = 'parola';
        maschera.setAttribute('aria-hidden', 'true');
        const interno = document.createElement('span');
        interno.textContent = parola;
        interno.style.setProperty('--ritardo', (i * 60) + 'ms');
        maschera.appendChild(interno);
        titolo.appendChild(maschera);
        if (i < parole.length - 1) titolo.appendChild(document.createTextNode(' '));
      });
    });
  }

  // ------------------------------------------------------------------
  // 3) NUMERI CHE CONTANO
  // ------------------------------------------------------------------
  function preparaContatori(radice) {
    radice.querySelectorAll('[data-conta]:not([data-contato])').forEach(function (el) {
      el.setAttribute('data-contato', '');
      const arrivo = parseInt(el.getAttribute('data-conta'), 10) || 0;
      if (pocoMovimento || arrivo === 0) { el.textContent = arrivo; return; }

      const durata = 900;
      let inizio = null;
      el.textContent = '0';

      function passo(adesso) {
        if (inizio === null) inizio = adesso;
        const t = Math.min((adesso - inizio) / durata, 1);
        const morbido = 1 - Math.pow(1 - t, 3); // parte veloce e rallenta alla fine
        el.textContent = Math.round(arrivo * morbido);
        if (t < 1) requestAnimationFrame(passo);
      }
      // parto con un piccolo ritardo, quando la scheda e' gia comparsa
      setTimeout(function () { requestAnimationFrame(passo); }, 350);
    });
  }

  // ------------------------------------------------------------------
  // 4) LUCE CHE SEGUE IL MOUSE SULLE SCHEDE
  // Scrivo la posizione del mouse in due variabili CSS: il gradiente
  // in stile.css le usa come centro della luce.
  // ------------------------------------------------------------------
  function preparaLuci(radice) {
    radice.querySelectorAll('[data-luce]:not([data-luce-pronta])').forEach(function (scheda) {
      scheda.setAttribute('data-luce-pronta', '');
      scheda.addEventListener('pointermove', function (evento) {
        const r = scheda.getBoundingClientRect();
        scheda.style.setProperty('--mx', (evento.clientX - r.left) + 'px');
        scheda.style.setProperty('--my', (evento.clientY - r.top) + 'px');
      });
    });
  }

  // Da chiamare dopo aver aggiunto elementi alla pagina con JavaScript
  // (lo fa il portale quando riceve i dati dalle API).
  function aggiorna(radice) {
    radice = radice || document;
    preparaTitoli(radice);
    preparaIngressi(radice);
    preparaContatori(radice);
    preparaLuci(radice);
  }
  window.Animazioni = { aggiorna: aggiorna };

  // ------------------------------------------------------------------
  // 5) TOAST: spariscono da soli dopo qualche secondo
  // ------------------------------------------------------------------
  function preparaToast() {
    document.querySelectorAll('.toast').forEach(function (toast, i) {
      setTimeout(function () {
        toast.classList.add('esce');
        toast.addEventListener('animationend', function () { toast.remove(); }, { once: true });
      }, 4200 + i * 400);
    });
  }

  // ------------------------------------------------------------------
  // 6) CONFERMA PRIMA DELLE AZIONI CHE NON SI POSSONO ANNULLARE
  // Uso l'elemento <dialog> del browser al posto del classico confirm():
  // cosi la finestra ha lo stile del sito e si chiude anche con Esc.
  // ------------------------------------------------------------------
  function preparaConferme() {
    const form = document.querySelectorAll('form[data-conferma]');
    if (!form.length || typeof HTMLDialogElement === 'undefined') return;

    const dialogo = document.createElement('dialog');
    dialogo.className = 'conferma';
    dialogo.innerHTML =
      '<h2></h2><p class="tenue"></p>' +
      '<div class="azioni">' +
      '<button type="button" class="btn" data-annulla>Annulla</button>' +
      '<button type="button" class="btn btn-primario" data-ok></button>' +
      '</div>';
    document.body.appendChild(dialogo);

    let formInAttesa = null;

    form.forEach(function (f) {
      f.addEventListener('submit', function (evento) {
        if (f.dataset.confermato === 'si') return; // secondo giro: lascio partire l'invio
        evento.preventDefault();

        // Prima di aprire la finestra avviso la pagina con un evento "conferma:prima".
        // Se la pagina lo annulla (es. mancano risposte obbligatorie) la finestra non si apre.
        const prima = new CustomEvent('conferma:prima', { cancelable: true });
        if (!f.dispatchEvent(prima)) return;

        formInAttesa = f;
        dialogo.querySelector('h2').textContent = f.dataset.confermaTitolo || 'Confermi?';
        dialogo.querySelector('p').textContent = f.dataset.conferma;
        const ok = dialogo.querySelector('[data-ok]');
        ok.textContent = f.dataset.confermaAzione || 'Conferma';
        ok.className = 'btn ' + (f.dataset.confermaTipo === 'pericolo' ? 'btn-pericolo' : 'btn-primario');
        dialogo.showModal();
      });
    });

    dialogo.querySelector('[data-annulla]').addEventListener('click', function () { dialogo.close(); });
    dialogo.querySelector('[data-ok]').addEventListener('click', function () {
      dialogo.close();
      if (formInAttesa) {
        formInAttesa.dataset.confermato = 'si';
        formInAttesa.requestSubmit();
      }
    });
  }

  // ------------------------------------------------------------------
  // 7) "COSTELLAZIONE": la decorazione animata dello sfondo
  // Tante stelline che vagano piano; quelle vicine tra loro si uniscono
  // con una linea sottile, come nelle mappe delle costellazioni.
  //  - intorno al mouse le stelle si accendono e si collegano al cursore
  //    con dei "fili" di luce;
  //  - con un clic (o un tocco) parte un'onda circolare che si allarga:
  //    al suo passaggio le stelle si accendono e vengono spinte via,
  //    come un sasso lanciato nell'acqua.
  // E' disegnata su <canvas> per restare leggera.
  // ------------------------------------------------------------------
  function preparaCostellazione() {
    document.querySelectorAll('canvas[data-costellazione]').forEach(function (canvas) {
      const ctx = canvas.getContext('2d');
      if (!ctx) return;

      // regolazioni dell'effetto
      const PIXEL_PER_STELLA = 21000; // una stella ogni tot pixel quadrati di sfondo
      const MAX_STELLE = 140;         // tetto, cosi sui monitor enormi non rallenta
      const DISTANZA_LINEA = 170;     // px: sotto questa distanza due stelle si collegano
      const RAGGIO_MOUSE = 210;       // px: fin dove arriva l'effetto intorno al mouse
      const VELOCITA_ONDA = 520;      // px al secondo
      const SPESSORE_ONDA = 60;       // px: larghezza della cresta dell'onda
      const FORZA_ONDA = 26;          // px: spostamento massimo al passaggio dell'onda
      const DURATA_ONDA = 2.2;        // secondi prima che l'onda si spenga
      const MAX_ONDE = 6;             // onde contemporanee al massimo
      // i due colori, scritti come "rosso, verde, blu" per poterci attaccare la trasparenza
      const COLORE_STELLA = '200, 214, 255'; // bianco-azzurro, a riposo
      const COLORE_ACCESA = '138, 180, 255'; // azzurro dell'accento, vicino al mouse

      let larghezza = 0, altezza = 0;
      let stelle = [];
      // "x, y" e' dove sta il mouse, "mx, my" lo insegue con un po' di ritardo:
      // cosi l'effetto scivola invece di scattare. "presenza" sfuma da 0 a 1
      // quando il mouse entra nella finestra e torna a 0 quando esce.
      const mouse = { x: 0, y: 0, mx: 0, my: 0, dentro: false, presenza: 0, nuovo: true };
      const onde = [];
      let visibile = true;
      let inCorso = false;   // c'e' gia un fotogramma in programma?
      let tempoPrima = 0;

      function nuovaStella() {
        const direzione = Math.random() * Math.PI * 2;
        const passo = 3 + Math.random() * 7; // px al secondo: si muovono appena
        return {
          x: Math.random() * larghezza,
          y: Math.random() * altezza,
          vx: Math.cos(direzione) * passo,
          vy: Math.sin(direzione) * passo,
          // ogni stella ha una "fase" casuale: cosi non brillano tutte insieme
          fase: Math.random() * Math.PI * 2,
          grande: Math.random() < 0.18,
          // calcolati a ogni fotogramma: posizione spostata dalle onde e quanto e' accesa
          sx: 0, sy: 0, luce: 0
        };
      }

      function ridimensiona() {
        const r = canvas.getBoundingClientRect();
        const densita = Math.min(window.devicePixelRatio || 1, 2);
        const larghezzaPrima = larghezza, altezzaPrima = altezza;
        larghezza = r.width; altezza = r.height;
        canvas.width = Math.round(larghezza * densita);
        canvas.height = Math.round(altezza * densita);
        ctx.setTransform(densita, 0, 0, densita, 0, 0);

        // le stelle che ci sono gia le tengo, spostandole in proporzione:
        // cosi quando la finestra cambia misura il cielo non "salta"
        if (larghezzaPrima > 0 && altezzaPrima > 0) {
          stelle.forEach(function (s) {
            s.x *= larghezza / larghezzaPrima;
            s.y *= altezza / altezzaPrima;
          });
        }
        // poi ne aggiungo o ne tolgo finche' sono quante ne servono per questa misura
        const quante = Math.min(MAX_STELLE, Math.round(larghezza * altezza / PIXEL_PER_STELLA));
        while (stelle.length < quante) stelle.push(nuovaStella());
        stelle.length = quante;
      }

      function programma() {
        if (inCorso) return;
        inCorso = true;
        requestAnimationFrame(disegna);
      }

      function disegna(tempo) {
        inCorso = false;
        const t = tempo / 1000;
        const dt = tempoPrima ? Math.min(t - tempoPrima, 0.05) : 0.016;
        tempoPrima = t;

        // il mouse "morbido" recupera una parte della distanza a ogni fotogramma
        const segui = 1 - Math.exp(-dt * 12);
        mouse.mx += (mouse.x - mouse.mx) * segui;
        mouse.my += (mouse.y - mouse.my) * segui;
        mouse.presenza += ((mouse.dentro ? 1 : 0) - mouse.presenza) * (1 - Math.exp(-dt * 5));

        // aggiorno le onde: raggio che cresce, forza che cala; tolgo quelle finite
        for (let k = onde.length - 1; k >= 0; k--) {
          const eta = t - onde[k].inizio;
          if (eta > DURATA_ONDA) { onde.splice(k, 1); continue; }
          onde[k].raggio = Math.max(0, eta) * VELOCITA_ONDA;
          onde[k].forza = Math.pow(1 - Math.max(0, eta) / DURATA_ONDA, 2);
        }

        // --- 1. muovo le stelle e calcolo quanto e' accesa ognuna ---
        const bordo = 20; // escono un po' fuori prima di rientrare dal lato opposto
        for (let i = 0; i < stelle.length; i++) {
          const s = stelle[i];
          if (!pocoMovimento) {
            s.x += s.vx * dt;
            s.y += s.vy * dt;
            if (s.x < -bordo) s.x = larghezza + bordo; else if (s.x > larghezza + bordo) s.x = -bordo;
            if (s.y < -bordo) s.y = altezza + bordo; else if (s.y > altezza + bordo) s.y = -bordo;
          }

          let ox = 0, oy = 0, luce = 0;

          // vicino al mouse
          if (mouse.presenza > 0.01) {
            const dx = s.x - mouse.mx, dy = s.y - mouse.my;
            const d = Math.sqrt(dx * dx + dy * dy);
            if (d < RAGGIO_MOUSE) {
              const f = 1 - d / RAGGIO_MOUSE;                 // 1 sotto il mouse, 0 al bordo
              luce += f * f * (3 - 2 * f) * mouse.presenza;   // "smoothstep": morbida ai bordi
            }
          }

          // onde dei clic
          for (let k = 0; k < onde.length; k++) {
            const o = onde[k];
            const dx = s.x - o.x, dy = s.y - o.y;
            const d = Math.sqrt(dx * dx + dy * dy) || 0.001;
            const scarto = d - o.raggio;                      // distanza dalla cresta
            if (scarto > SPESSORE_ONDA * 2.5 || scarto < -SPESSORE_ONDA * 2.5) continue;
            // "busta" a campana: vale 1 sulla cresta e si spegne allontanandosi
            const busta = Math.exp(-(scarto * scarto) / (SPESSORE_ONDA * SPESSORE_ONDA)) * o.forza;
            ox += (dx / d) * busta * FORZA_ONDA;
            oy += (dy / d) * busta * FORZA_ONDA;
            luce += busta;
          }

          s.sx = s.x + ox;
          s.sy = s.y + oy;
          s.luce = Math.min(1, luce);
        }

        ctx.clearRect(0, 0, larghezza, altezza);
        ctx.lineWidth = 1;

        // --- 2. le linee tra le stelle vicine ---
        // confronto ogni stella con tutte le successive (sono poche, si puo fare)
        for (let i = 0; i < stelle.length; i++) {
          const a = stelle[i];
          for (let j = i + 1; j < stelle.length; j++) {
            const b = stelle[j];
            const dx = a.sx - b.sx, dy = a.sy - b.sy;
            const d2 = dx * dx + dy * dy;
            if (d2 > DISTANZA_LINEA * DISTANZA_LINEA) continue;
            const vicinanza = 1 - Math.sqrt(d2) / DISTANZA_LINEA; // piu sono vicine, piu la linea si vede
            const luce = Math.max(a.luce, b.luce);
            ctx.beginPath();
            ctx.moveTo(a.sx, a.sy);
            ctx.lineTo(b.sx, b.sy);
            ctx.strokeStyle = 'rgba(' + (luce > 0.05 ? COLORE_ACCESA : COLORE_STELLA) + ', ' + (vicinanza * (0.22 + luce * 0.6)) + ')';
            ctx.stroke();
          }
        }

        // --- 3. i fili di luce dal mouse alle stelle vicine ---
        if (mouse.presenza > 0.05) {
          for (let i = 0; i < stelle.length; i++) {
            const s = stelle[i];
            const dx = s.sx - mouse.mx, dy = s.sy - mouse.my;
            const d = Math.sqrt(dx * dx + dy * dy);
            if (d > RAGGIO_MOUSE) continue;
            ctx.beginPath();
            ctx.moveTo(mouse.mx, mouse.my);
            ctx.lineTo(s.sx, s.sy);
            ctx.strokeStyle = 'rgba(' + COLORE_ACCESA + ', ' + ((1 - d / RAGGIO_MOUSE) * 0.7 * mouse.presenza) + ')';
            ctx.stroke();
          }
        }

        // --- 4. il fronte di ogni onda, un cerchio appena visibile ---
        for (let k = 0; k < onde.length; k++) {
          const o = onde[k];
          ctx.beginPath();
          ctx.arc(o.x, o.y, o.raggio, 0, Math.PI * 2);
          ctx.strokeStyle = 'rgba(' + COLORE_ACCESA + ', ' + (o.forza * 0.18) + ')';
          ctx.lineWidth = 1.5;
          ctx.stroke();
        }
        ctx.lineWidth = 1;

        // --- 5. le stelle ---
        for (let i = 0; i < stelle.length; i++) {
          const s = stelle[i];
          const brilla = (Math.sin(t * 1.2 + s.fase) + 1) / 2; // tra 0 e 1, sale e scende piano
          const raggio = (s.grande ? 2.2 : 1.5) + s.luce * 2.4;
          ctx.beginPath();
          ctx.arc(s.sx, s.sy, raggio, 0, Math.PI * 2);
          ctx.fillStyle = 'rgba(' + (s.luce > 0.05 ? COLORE_ACCESA : COLORE_STELLA) + ', ' + Math.min(1, 0.35 + brilla * 0.25 + s.luce * 0.5) + ')';
          ctx.fill();
        }

        if (!pocoMovimento && visibile) programma();
      }

      // posizione dell'evento rispetto al canvas
      function posizione(evento) {
        const r = canvas.getBoundingClientRect();
        return { x: evento.clientX - r.left, y: evento.clientY - r.top };
      }

      ridimensiona();
      programma();

      window.addEventListener('resize', function () {
        ridimensiona();
        programma();
      });

      // il mouse lo ascolto su tutta la finestra: il canvas sta sotto ai contenuti
      window.addEventListener('pointermove', function (evento) {
        const pos = posizione(evento);
        mouse.x = pos.x; mouse.y = pos.y;
        if (mouse.nuovo) { mouse.mx = pos.x; mouse.my = pos.y; mouse.nuovo = false; }
        mouse.dentro = true;
      });

      // clic o tocco: parte un'onda dal punto premuto
      window.addEventListener('pointerdown', function (evento) {
        if (pocoMovimento) return;
        const pos = posizione(evento);
        if (pos.x < 0 || pos.y < 0 || pos.x > larghezza || pos.y > altezza) return; // fuori dal canvas
        onde.push({ x: pos.x, y: pos.y, inizio: performance.now() / 1000, raggio: 0, forza: 1 });
        if (onde.length > MAX_ONDE) onde.shift();
        if (evento.pointerType !== 'mouse') {
          // col dito non c'e' un "passaggio": sposto l'effetto dove si tocca
          mouse.x = mouse.mx = pos.x; mouse.y = mouse.my = pos.y;
          mouse.dentro = true;
        }
      });

      // quando il dito si alza o il mouse esce dalla finestra l'effetto sfuma via
      function spegni(evento) {
        if (evento && evento.pointerType === 'mouse' && evento.type !== 'pointerleave') return;
        mouse.dentro = false;
        mouse.nuovo = true;
      }
      window.addEventListener('pointerup', spegni);
      window.addEventListener('pointercancel', spegni);
      document.documentElement.addEventListener('pointerleave', spegni);
      window.addEventListener('blur', function () { spegni(); });

      // se la decorazione esce dallo schermo smetto di ridisegnarla (risparmio batteria)
      if ('IntersectionObserver' in window) {
        new IntersectionObserver(function (voci) {
          const eraVisibile = visibile;
          visibile = voci[0].isIntersecting;
          if (visibile && !eraVisibile) programma();
        }).observe(canvas);
      }
    });
  }

  // ------------------------------------------------------------------
  // AVVIO
  // ------------------------------------------------------------------
  function avvia() {
    aggiorna(document);
    preparaToast();
    preparaConferme();
    preparaCostellazione();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', avvia);
  } else {
    avvia();
  }
})();
