-- =====================================================================
-- Project Work - Piattaforma questionari online
-- schema.sql -> crea il database e tutte le tabelle
-- Autore: Leon Sciurti
--
-- ATTENZIONE: se il db esiste gia lo cancella e lo ricrea da zero
-- (cosi lo script si puo rilanciare quante volte vuoi)
-- =====================================================================

DROP DATABASE IF EXISTS questionari_pw;
CREATE DATABASE questionari_pw
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE questionari_pw;


-- ---------------------------------------------------------------------
-- UTENTI
-- sia l'admin che gli utenti normali stanno qui, li distingue il ruolo
-- la password NON e' in chiaro, salviamo solo l'hash bcrypt
--
-- PROFILO (le 4 categorie usate per scegliere i destinatari dei questionari):
--   sesso, data_nascita (da cui si calcola la fascia d'eta), area_geografica,
--   occupazione. Sono NULL finche non vengono indicati (dall'admin nel
--   Back Office o dall'utente stesso nel portale/app).
-- Salvo la data di nascita e non la fascia: cosi la fascia resta sempre giusta
-- anche con il passare degli anni.
-- ---------------------------------------------------------------------
CREATE TABLE utenti (
  id               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  username         VARCHAR(50)   NOT NULL,
  password_hash    VARCHAR(255)  NOT NULL,
  nome             VARCHAR(100)  NOT NULL,
  ruolo            ENUM('ADMIN','UTENTE') NOT NULL DEFAULT 'UTENTE',
  sesso            ENUM('UOMO','DONNA','ALTRO') NULL,
  data_nascita     DATE          NULL,
  area_geografica  ENUM('NORD','CENTRO','SUD_ISOLE') NULL,
  occupazione      ENUM('STUDENTE','LAVORATORE','DISOCCUPATO','PENSIONATO') NULL,
  creato_il        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY uq_utenti_username (username)   -- niente username doppi
) ENGINE=InnoDB;


-- ---------------------------------------------------------------------
-- QUESTIONARI
-- stato: BOZZA -> PUBBLICATO -> CHIUSO (solo in avanti, lo controlla il codice)
-- le date di pubblicazione/chiusura restano NULL finche non succede
--
-- DESTINATARI: per ogni categoria del profilo, i valori ammessi.
--   ''              -> nessun filtro, tutti gli utenti
--   'UOMO'          -> solo gli uomini
--   'UNDER_30,OVER_50' -> under 30 OPPURE over 50
-- Tra categorie diverse vale l'AND: un utente vede il questionario solo se
-- rientra in TUTTE le categorie filtrate. Chi non ha indicato un dato resta
-- fuori dai questionari filtrati proprio su quel dato.
-- Il tipo SET accetta solo i valori elencati: un valore inventato viene rifiutato.
-- Fasce d'eta: UNDER_30 (< 30 anni), DA_30_A_50 (30-50), OVER_50 (> 50).
-- ---------------------------------------------------------------------
CREATE TABLE questionari (
  id                INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  titolo            VARCHAR(200)  NOT NULL,
  descrizione       TEXT          NOT NULL,
  stato             ENUM('BOZZA','PUBBLICATO','CHIUSO') NOT NULL DEFAULT 'BOZZA',
  dest_sesso        SET('UOMO','DONNA','ALTRO') NOT NULL DEFAULT '',
  dest_eta          SET('UNDER_30','DA_30_A_50','OVER_50') NOT NULL DEFAULT '',
  dest_area         SET('NORD','CENTRO','SUD_ISOLE') NOT NULL DEFAULT '',
  dest_occupazione  SET('STUDENTE','LAVORATORE','DISOCCUPATO','PENSIONATO') NOT NULL DEFAULT '',
  creato_da       INT UNSIGNED  NOT NULL,
  creato_il       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  pubblicato_il   DATETIME      NULL,
  chiuso_il       DATETIME      NULL,

  PRIMARY KEY (id),
  KEY idx_questionari_stato (stato),          -- l'elenco "pubblicati" filtra per stato
  CONSTRAINT fk_questionari_utente
    FOREIGN KEY (creato_da) REFERENCES utenti (id)
) ENGINE=InnoDB;


-- ---------------------------------------------------------------------
-- DOMANDE
-- tipologia: APERTA (testo libero), SINGOLA (1 opzione), MULTIPLA (1+ opzioni)
-- se cancello un questionario in bozza le sue domande spariscono con lui (CASCADE)
-- ---------------------------------------------------------------------
CREATE TABLE domande (
  id               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  questionario_id  INT UNSIGNED  NOT NULL,
  testo            VARCHAR(500)  NOT NULL,
  tipologia        ENUM('APERTA','SINGOLA','MULTIPLA') NOT NULL,
  obbligatoria     BOOLEAN       NOT NULL DEFAULT FALSE,
  ordine           INT UNSIGNED  NOT NULL DEFAULT 0,  -- per mostrarle nell'ordine giusto

  PRIMARY KEY (id),
  KEY idx_domande_questionario (questionario_id, ordine),
  CONSTRAINT fk_domande_questionario
    FOREIGN KEY (questionario_id) REFERENCES questionari (id)
    ON DELETE CASCADE
) ENGINE=InnoDB;


-- ---------------------------------------------------------------------
-- OPZIONI
-- solo per le domande SINGOLA / MULTIPLA (almeno 2, lo controlla il codice)
-- l'unique (id, domanda_id) serve alla FK composta in "risposte", vedi sotto
-- ---------------------------------------------------------------------
CREATE TABLE opzioni (
  id          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  domanda_id  INT UNSIGNED  NOT NULL,
  testo       VARCHAR(255)  NOT NULL,
  ordine      INT UNSIGNED  NOT NULL DEFAULT 0,

  PRIMARY KEY (id),
  UNIQUE KEY uq_opzioni_id_domanda (id, domanda_id),
  KEY idx_opzioni_domanda (domanda_id, ordine),
  CONSTRAINT fk_opzioni_domanda
    FOREIGN KEY (domanda_id) REFERENCES domande (id)
    ON DELETE CASCADE
) ENGINE=InnoDB;


-- ---------------------------------------------------------------------
-- COMPILAZIONI
-- una riga = "l'utente X ha compilato il questionario Y"
-- L'UNIQUE (questionario_id, utente_id) e' IL vincolo chiave del progetto:
-- una sola compilazione per utente e questionario, web o mobile che sia.
-- Anche se il codice avesse un bug, il db rifiuta il secondo invio.
-- RESTRICT: non posso cancellare un questionario/utente che ha compilazioni
-- ---------------------------------------------------------------------
CREATE TABLE compilazioni (
  id               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  questionario_id  INT UNSIGNED  NOT NULL,
  utente_id        INT UNSIGNED  NOT NULL,
  canale           ENUM('WEB','MOBILE') NOT NULL,
  inviata_il       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY uq_compilazione_utente_questionario (questionario_id, utente_id),
  KEY idx_compilazioni_utente (utente_id),
  CONSTRAINT fk_compilazioni_questionario
    FOREIGN KEY (questionario_id) REFERENCES questionari (id)
    ON DELETE RESTRICT,
  CONSTRAINT fk_compilazioni_utente
    FOREIGN KEY (utente_id) REFERENCES utenti (id)
    ON DELETE RESTRICT
) ENGINE=InnoDB;


-- ---------------------------------------------------------------------
-- RISPOSTE
-- come si salva ogni tipologia:
--   APERTA   -> 1 riga con "testo", opzione_id NULL
--   SINGOLA  -> 1 riga con opzione_id
--   MULTIPLA -> 1 riga PER OGNI opzione scelta
-- La FK composta (opzione_id, domanda_id) obbliga l'opzione a
-- appartenere proprio a quella domanda: non puoi rispondere alla
-- domanda 5 con un'opzione della domanda 9.
-- ---------------------------------------------------------------------
CREATE TABLE risposte (
  id               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  compilazione_id  INT UNSIGNED  NOT NULL,
  domanda_id       INT UNSIGNED  NOT NULL,
  opzione_id       INT UNSIGNED  NULL,
  testo            TEXT          NULL,

  PRIMARY KEY (id),
  UNIQUE KEY uq_risposta_opzione (compilazione_id, domanda_id, opzione_id), -- stessa opzione non 2 volte
  KEY idx_risposte_domanda (domanda_id),
  CONSTRAINT fk_risposte_compilazione
    FOREIGN KEY (compilazione_id) REFERENCES compilazioni (id)
    ON DELETE CASCADE,
  CONSTRAINT fk_risposte_domanda
    FOREIGN KEY (domanda_id) REFERENCES domande (id)
    ON DELETE RESTRICT,
  CONSTRAINT fk_risposte_opzione_domanda
    FOREIGN KEY (opzione_id, domanda_id) REFERENCES opzioni (id, domanda_id)
    ON DELETE RESTRICT,
  -- o c'e' un'opzione o c'e' del testo, mai tutti e due e mai nessuno
  CONSTRAINT chk_risposta_opzione_o_testo
    CHECK ((opzione_id IS NULL) <> (testo IS NULL))
) ENGINE=InnoDB;


-- ---------------------------------------------------------------------
-- TOKEN_API
-- quando un utente fa login dalle API (web o app) gli diamo un token
-- casuale; ad ogni richiesta il server cerca il token qui e capisce
-- CHI e' l'utente, senza mai fidarsi di un id mandato dal client
-- ---------------------------------------------------------------------
CREATE TABLE token_api (
  id          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  utente_id   INT UNSIGNED  NOT NULL,
  token       CHAR(64)      NOT NULL,
  creato_il   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  scade_il    DATETIME      NOT NULL,

  PRIMARY KEY (id),
  UNIQUE KEY uq_token (token),
  KEY idx_token_utente (utente_id),
  CONSTRAINT fk_token_utente
    FOREIGN KEY (utente_id) REFERENCES utenti (id)
    ON DELETE CASCADE
) ENGINE=InnoDB;
