# Questionari – Project Work

Piattaforma per la creazione, la pubblicazione e la compilazione di questionari online.
Realizzazione individuale di **Leon Sciurti**, UF "Project Work", ITS full-stack developer.

L'amministratore crea e pubblica i questionari dal **Back Office**, scegliendo eventualmente a quali **categorie di utenti** sono destinati; gli utenti li compilano dal **portale web** o dall'**app mobile Cordova**. Portale e app usano gli stessi **servizi REST in Java** e tutti i componenti condividono un unico **database MySQL**.

```
                ┌──────────────────────┐
  Back Office ──┤  PHP + PDO           ├──┐
  (admin)       └──────────────────────┘  │
                                           ├──►  MySQL  (questionari_pw)
  Portale web ──┐   ┌──────────────────┐   │
  (utenti)      ├──►│  API REST Java   ├───┘
  App Cordova ──┘   │  Spring Boot     │
  (utenti)          └──────────────────┘
```

---

## 1. Struttura del progetto

| Cartella | Contenuto |
|---|---|
| `database/` | `schema.sql` (database, tabelle, vincoli), `dati_collaudo.sql` (utenze di test) e `aggiornamento_destinatari.sql` (aggiorna un database creato con la versione precedente) |
| `api/` | Servizi REST in Java con Spring Boot (Maven) |
| `backoffice/` | Back Office dell'amministratore in PHP (MVC con front controller) |
| `client/` | Portale web degli utenti in HTML, CSS e JavaScript |
| `mobile/` | Progetto Apache Cordova (Android) |
| `stile/` | Tema grafico, animazioni e font condivisi da Back Office, portale e app |
| `Screenshot/` | Schermate di tutte le funzioni, nominate per funzione |

Il portale e l'app sono **lo stesso codice**: prima di ogni compilazione Cordova lo script `mobile/scripts/sincronizza.js` copia `client/` e `stile/` dentro `mobile/www/`, sostituendo solo la configurazione (indirizzo delle API e canale `MOBILE`).

---

## 2. Requisiti di esecuzione

| Componente | Versione usata | Note |
|---|---|---|
| MAMP (Apache + PHP + MySQL) | PHP 8.3, MySQL 8.0 | MySQL sulla porta **8889**, Apache sulla **8888** (impostazioni predefinite di MAMP) |
| Java JDK | 17 o superiore (sviluppato con 19) | Il progetto è compilato per Java 17 |
| Maven | 3.6.3 o superiore | Facoltativo: nel progetto c'è il Maven Wrapper `mvnw` |
| Node.js | 20.17 o superiore | Necessario per Cordova |
| Cordova CLI | 13 | `npm install -g cordova` |
| Android SDK + emulatore | API 35/36 | Installati con Android Studio; variabile `ANDROID_HOME` impostata |

Utente e password di MySQL usati da Back Office e API: `root` / `root` (predefiniti di MAMP).

> Le credenziali in questo README, in `backoffice/config/config.php` e in `api/src/main/resources/application.yaml` sono quelle predefinite di MAMP e le utenze di test inventate: servono solo per l'esecuzione in locale. In un ambiente reale vanno sostituite e tenute fuori dal repository.

---

## 3. Installazione e avvio

Gli indirizzi sotto valgono se la cartella del progetto si trova in `MAMP/htdocs/questionari_leonsciurti_pw`.

### 3.1 Database

1. Avviare MAMP.
2. Aprire phpMyAdmin (`http://localhost:8888/phpMyAdmin/`), scheda **SQL** o **Importa**.
3. Eseguire, nell'ordine:
   1. `database/schema.sql` – crea il database `questionari_pw` (se esiste già lo cancella e lo ricrea);
   2. `database/dati_collaudo.sql` – inserisce l'amministratore e i quattro utenti di test.

Se il database era già stato creato con la versione precedente (senza profili e destinatari) e si vogliono **conservare i dati**, basta eseguire una sola volta `database/aggiornamento_destinatari.sql`: aggiunge le nuove colonne, compila i profili degli utenti di test e crea `sneri`.

In alternativa da terminale:

```bash
/Applications/MAMP/Library/bin/mysql80/bin/mysql -h 127.0.0.1 -P 8889 -u root -proot < database/schema.sql
/Applications/MAMP/Library/bin/mysql80/bin/mysql -h 127.0.0.1 -P 8889 -u root -proot < database/dati_collaudo.sql
```

(il percorso del client `mysql` dipende dalla versione di MAMP). Rieseguire i due script riporta il database allo stato iniziale.

### 3.2 Servizi REST (Java)

```bash
cd api
mvn spring-boot:run        # oppure ./mvnw spring-boot:run
```

Le API sono pronte quando compare `Started QuestionariApiApplication`.
Indirizzo: **`http://localhost:8080/api`**. Per fermarle: `Ctrl+C`.

### 3.3 Back Office (PHP)

Nessuna installazione: con MAMP acceso è raggiungibile a
**`http://localhost:8888/questionari_leonsciurti_pw/backoffice/`**

Dopo l'accesso si apre la **Dashboard**: questionari aperti con la percentuale di destinatari che hanno risposto, cose da fare (bozze pronte da pubblicare o incomplete, questionari pubblicati che nessuno può compilare o a cui hanno già risposto tutti, utenti con il profilo incompleto), andamento delle compilazioni negli ultimi 14 giorni, ripartizione tra portale web e app, ultime compilazioni ricevute.

Nel dettaglio di un questionario **pubblicato o chiuso** con almeno una compilazione compaiono le **statistiche**: compilazioni, tasso di risposta sui destinatari, canale (web o app) e, per ogni domanda a scelta, quante volte è stata scelta ogni opzione e qual è la scelta più frequente per sesso, fascia d'età, area geografica e condizione lavorativa (calcolate sul profilo attuale degli utenti). Per le domande aperte mostra le risposte più recenti. Sono funzioni aggiuntive: la traccia non richiede analisi statistiche.

### 3.4 Portale web

Con MAMP e le API accesi:
**`http://localhost:8888/questionari_leonsciurti_pw/client/`**

### 3.5 App mobile (Cordova)

Con le API accese e un emulatore Android disponibile:

```bash
cd mobile
npm install                      # solo la prima volta, se manca node_modules
cordova platform add android     # solo la prima volta, se manca platforms/android
cordova run android --emulator   # compila, avvia l'emulatore e installa l'app
```

Per generare solo l'APK: `cordova build android` (file in `platforms/android/app/build/outputs/apk/debug/`).

Dall'emulatore l'app raggiunge le API all'indirizzo `http://10.0.2.2:8080/api`: `10.0.2.2` è l'indirizzo con cui l'emulatore Android vede il computer che lo ospita.

---

## 4. Credenziali delle utenze di test

| Ruolo | Username | Password | Dove si usa |
|---|---|---|---|
| Amministratore | `gestore` | `Gestore#2026` | Back Office |
| Utente | `mrossi` | `Compila#2026` | Portale web e app |
| Utente | `gbianchi` | `Compila#2026` | Portale web e app |
| Utente | `lverdi` | `Compila#2026` | Portale web e app |
| Utente | `sneri` | `Compila#2026` | Portale web e app (profilo da completare al primo accesso) |

Profili degli utenti di test (età calcolate a novembre 2026):

| Username | Nome | Sesso | Età | Area | Condizione |
|---|---|---|---|---|---|
| `mrossi` | Mario Rossi | Uomo | 28 (under 30) | Nord | Studente |
| `gbianchi` | Giulia Bianchi | Donna | 38 (30-50) | Centro | Lavoratore |
| `lverdi` | Luca Verdi | Uomo | 61 (over 50) | Sud e isole | Pensionato |
| `sneri` | Sara Neri | – | – | – | – |

Le password sono salvate nel database solo come hash bcrypt. La registrazione autonoma non è prevista.

---

## 5. Indirizzi dei servizi

| Servizio | Indirizzo |
|---|---|
| Back Office | `http://localhost:8888/questionari_leonsciurti_pw/backoffice/` |
| Portale web | `http://localhost:8888/questionari_leonsciurti_pw/client/` |
| API REST (dal computer) | `http://localhost:8080/api` |
| API REST (dall'emulatore Android) | `http://10.0.2.2:8080/api` |
| MySQL | `127.0.0.1:8889`, database `questionari_pw` |

### Endpoint REST

Tutti gli endpoint tranne il login richiedono l'intestazione `Authorization: Bearer <token>`. Le risposte sono in JSON; gli errori hanno sempre la forma `{ "errore": "messaggio" }`.

| Metodo | Percorso | Descrizione | Esiti principali |
|---|---|---|---|
| POST | `/api/auth/login` | Accesso utente, restituisce il token | 200, 400, 401, 403 (admin) |
| POST | `/api/auth/logout` | Annulla il token | 204 |
| GET | `/api/questionari` | Questionari pubblicati, con indicazione di quelli già compilati | 200, 401 |
| GET | `/api/questionari/{id}` | Questionario con domande e opzioni | 200, 404 (inesistente o bozza), 409 (chiuso) |
| POST | `/api/questionari/{id}/compilazioni` | Invio delle risposte | 201, 400, 404, 409 (chiuso o già compilato) |
| GET | `/api/compilazioni` | Compilazioni dell'utente autenticato | 200 |
| GET | `/api/compilazioni/{id}` | Una propria compilazione, in sola lettura | 200, 404 (inesistente o di un altro utente) |
| GET | `/api/profilo` | Profilo dell'utente autenticato | 200 |
| PUT | `/api/profilo` | Salva il proprio profilo (tutti e 4 i campi) | 200, 400 |

`GET /api/questionari` restituisce solo i questionari destinati all'utente (più quelli che ha già compilato). `GET /api/questionari/{id}` e l'invio rispondono **404** anche quando l'utente non rientra tra i destinatari. Il login restituisce anche `profiloCompleto`.

Esempio di invio:

```json
POST /api/questionari/1/compilazioni
{
  "canale": "WEB",
  "risposte": [
    { "domandaId": 1, "testo": "Testo libero" },
    { "domandaId": 2, "opzioniIds": [5] },
    { "domandaId": 3, "opzioniIds": [8, 9] }
  ]
}
```

Esempio di profilo:

```json
PUT /api/profilo
{ "sesso": "DONNA", "dataNascita": "1990-05-01", "areaGeografica": "NORD", "occupazione": "LAVORATORE" }
```

---

## 5-bis. Profilo utente e destinatari dei questionari

Ogni utente ha un **profilo** con quattro categorie, che decidono quali questionari vede:

| Categoria | Valori | Dato salvato |
|---|---|---|
| Sesso | Uomo, Donna, Altro | `utenti.sesso` |
| Fascia d'età | Under 30 (< 30), Da 30 a 50, Over 50 (> 50) | `utenti.data_nascita` (la fascia si calcola, così non invecchia) |
| Area geografica | Nord, Centro, Sud e isole | `utenti.area_geografica` |
| Condizione lavorativa | Studente, Lavoratore, Disoccupato, Pensionato | `utenti.occupazione` |

**Chi compila il profilo**

- l'amministratore, dalla nuova sezione **Utenti** del Back Office (elenco, nuovo utente, modifica di nome, password e profilo); i campi si possono lasciare vuoti;
- l'utente stesso, dalla pagina **Profilo** del portale e dell'app. Se al login il profilo è incompleto, l'utente viene portato lì prima di vedere i questionari.

**Destinatari di un questionario**

Nel form del questionario (solo in bozza, come titolo e domande) l'amministratore sceglie, per ogni categoria, uno o più valori:

- nessun valore in una categoria = quella categoria non filtra;
- più valori nella stessa categoria = basta uno (es. *Under 30* **o** *Over 50*);
- tra categorie diverse vale l'**AND** (es. *Donna* **e** *Nord*);
- se un utente non ha indicato un dato, è escluso dai questionari filtrati su quel dato.

Chi non rientra tra i destinatari **non vede il questionario**: non compare nell'elenco e le API rispondono 404, come per una bozza. Il dettaglio del questionario nel Back Office mostra i filtri e quanti utenti ci rientrano oggi. Nel database i filtri sono colonne `SET` di MySQL (`dest_sesso`, `dest_eta`, `dest_area`, `dest_occupazione`), dove il valore vuoto significa "tutti".

---

## 6. Dove cambiare la configurazione

| Cosa | File |
|---|---|
| Connessione MySQL del Back Office | `backoffice/config/config.php` |
| Porta delle API e connessione MySQL | `api/src/main/resources/application.yaml` |
| Indirizzo delle API per il portale | `client/js/config.js` e `connect-src` nei file `client/*.html` |
| Indirizzo delle API per l'app (es. telefono vero) | `mobile/config.mobile.js` (la `Content-Security-Policy` viene aggiornata dallo script di sincronizzazione) |

---

## 7. Scenario di collaudo (sezione 5 della traccia)

1. **Back Office** – accedere come `gestore`.
2. Creare un questionario (nasce in **Bozza**) e aggiungere almeno una domanda per tipologia: aperta, a risposta singola, a risposta multipla, con domande obbligatorie e facoltative. Modificare la bozza.
3. Creare una seconda bozza ed **eliminarla**.
4. **Pubblicare** il primo questionario. Da questo momento i comandi di modifica ed eliminazione spariscono e, se forzati, vengono respinti dal server.
5. **Portale web** – accedere come `mrossi` e inviare una compilazione (le domande obbligatorie sono verificate).
6. **App** – accedere come `gbianchi` e inviare una compilazione dall'app.
7. Con `gbianchi` provare a compilare di nuovo lo stesso questionario dal portale web: il server risponde *"Hai già compilato questo questionario"*.
8. **Back Office** – consultare le compilazioni ricevute (canale web e app) e le singole risposte.
9. Con `mrossi` aprire *Le mie risposte*: si vedono solo le proprie compilazioni, in sola lettura.
10. **Back Office** – **chiudere** il questionario.
11. Accedere come `lverdi`: il questionario non è più compilabile, né dal portale né dall'app.

**Destinatari**

12. **Back Office** – creare un questionario destinato solo a *Donna* e *Da 30 a 50 anni* e pubblicarlo (il dettaglio indica 1 utente).
13. Accedere come `mrossi` o `lverdi`: il questionario non compare, e aprendolo dall'indirizzo il server risponde *"Questionario non trovato"*.
14. Accedere come `gbianchi`: il questionario compare e si può compilare.
15. Accedere come `sneri`: il portale chiede di completare il profilo; dopo aver indicato *Donna* e una data di nascita tra 30 e 50 anni il questionario compare.

### Controlli lato server verificati

- Solo l'amministratore entra nel Back Office; l'amministratore non può accedere a portale e app.
- Le API ricavano l'utente dal token, mai da un id inviato dal client: un utente non può leggere le compilazioni di un altro.
- Prima di ogni invio: questionario pubblicato, nessuna compilazione precedente, domande obbligatorie compilate, opzioni appartenenti alla domanda, una sola opzione per le domande a risposta singola, niente testo sulle domande a scelta e niente opzioni su quelle aperte.
- Destinatari: l'elenco, il dettaglio e l'invio verificano lato server che l'utente rientri nei destinatari, leggendo il profilo dal database (mai dai dati del client).
- Profilo: valori fuori elenco e date di nascita non valide (nel futuro, età sotto 14 o sopra 120 anni) vengono rifiutati sia dalle API sia dal Back Office.
- Una sola compilazione per utente e questionario, garantita anche dal vincolo `UNIQUE (questionario_id, utente_id)` del database (verificato con invii simultanei).
- Modifica, eliminazione e cambi di stato avvengono solo se lo stato lo consente; il ciclo Bozza → Pubblicato → Chiuso non torna indietro.
- Il Back Office protegge ogni modifica con token CSRF e accetta solo richieste POST.

---

## 8. Screenshot

La cartella `Screenshot/` contiene le schermate in PNG, nominate per area e funzione e numerate nell'ordine dello scenario di collaudo:

- `backoffice-*` – accesso (anche errato), dashboard, elenco, creazione di un questionario e delle domande delle tre tipologie, errore sulle opzioni, eliminazione di una bozza, pubblicazione, modifica respinta su un pubblicato, compilazioni ricevute, risposte di un utente, statistiche, chiusura, utenti, vista su telefono;
- `portale-*` – accesso, amministratore respinto, questionari da compilare, compilazione con verifica delle obbligatorie, conferma di invio e di salvataggio, storico e risposte in sola lettura, risposte di un altro utente non visibili, profilo, seconda compilazione respinta, questionario chiuso, vista su telefono.
- `app-*` – l'app Cordova in esecuzione sull'emulatore Android: accesso, questionari da compilare, compilazione, conferma di salvataggio.

Gli screenshot documentano le funzioni ma non sostituiscono il collaudo funzionale.

---

## 9. Note tecniche

- **CORS** – Il portale (porta 8888) e l'app chiamano le API (porta 8080), cioè un'origine diversa: le API autorizzano queste chiamate. Non usando cookie ma un token nell'intestazione, una richiesta senza token non ottiene comunque nulla.
- **HTTP in chiaro su Android** – In locale le API sono in `http`. In `mobile/config.xml` l'app è servita in `http` e il traffico non cifrato è autorizzato. In produzione le API andrebbero esposte in `https` e queste eccezioni rimosse.
- **Font** – Plus Jakarta Sans è incluso nel progetto (licenza SIL Open Font License, vedi `stile/font/LICENZA.txt`), così funziona anche nell'app senza connessione.
- **Cartelle generate** – `api/target`, `mobile/node_modules` e `mobile/platforms` si ricreano con i comandi della sezione 3 e possono essere escluse dalla consegna.
