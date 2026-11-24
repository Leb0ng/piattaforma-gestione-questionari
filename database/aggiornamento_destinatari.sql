-- =====================================================================
-- Project Work - Piattaforma questionari online
-- aggiornamento_destinatari.sql -> aggiunge profilo utente e destinatari
-- dei questionari a un database GIA esistente, senza perdere i dati.
--
-- Serve solo se il database e' stato creato con la versione precedente di
-- schema.sql. Su un'installazione nuova bastano schema.sql + dati_collaudo.sql.
-- Va lanciato UNA volta sola (una seconda volta darebbe "colonna gia esistente").
-- =====================================================================

USE questionari_pw;

-- profilo dell'utente: le 4 categorie (NULL = non indicato)
ALTER TABLE utenti
  ADD COLUMN sesso            ENUM('UOMO','DONNA','ALTRO') NULL AFTER ruolo,
  ADD COLUMN data_nascita     DATE NULL AFTER sesso,
  ADD COLUMN area_geografica  ENUM('NORD','CENTRO','SUD_ISOLE') NULL AFTER data_nascita,
  ADD COLUMN occupazione      ENUM('STUDENTE','LAVORATORE','DISOCCUPATO','PENSIONATO') NULL AFTER area_geografica;

-- destinatari del questionario: '' = tutti. I questionari gia esistenti
-- restano quindi visibili a tutti, come prima.
ALTER TABLE questionari
  ADD COLUMN dest_sesso        SET('UOMO','DONNA','ALTRO') NOT NULL DEFAULT '' AFTER stato,
  ADD COLUMN dest_eta          SET('UNDER_30','DA_30_A_50','OVER_50') NOT NULL DEFAULT '' AFTER dest_sesso,
  ADD COLUMN dest_area         SET('NORD','CENTRO','SUD_ISOLE') NOT NULL DEFAULT '' AFTER dest_eta,
  ADD COLUMN dest_occupazione  SET('STUDENTE','LAVORATORE','DISOCCUPATO','PENSIONATO') NOT NULL DEFAULT '' AFTER dest_area;

-- profili delle utenze di test (gli stessi di dati_collaudo.sql)
UPDATE utenti SET sesso = 'UOMO',  data_nascita = '1998-04-12', area_geografica = 'NORD',      occupazione = 'STUDENTE'   WHERE username = 'mrossi';
UPDATE utenti SET sesso = 'DONNA', data_nascita = '1988-09-23', area_geografica = 'CENTRO',    occupazione = 'LAVORATORE' WHERE username = 'gbianchi';
UPDATE utenti SET sesso = 'UOMO',  data_nascita = '1965-02-07', area_geografica = 'SUD_ISOLE', occupazione = 'PENSIONATO' WHERE username = 'lverdi';

-- quarto utente senza profilo, per provare il completamento al primo accesso
INSERT IGNORE INTO utenti (username, password_hash, nome, ruolo) VALUES
  ('sneri', '$2y$10$1E6nLxfLOac5j9aZQcP2CeNySZ0MWodRb4iedfVIFYZwRRWeGBcYq', 'Sara Neri', 'UTENTE');
