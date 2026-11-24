-- =====================================================================
-- Project Work - Piattaforma questionari online
-- dati_collaudo.sql -> utenze di test per lo scenario della sezione 5
-- da lanciare DOPO schema.sql
--
-- Credenziali (da riportare anche nel README):
--   gestore  / Gestore#2026 -> amministratore (Back Office)
--   mrossi  / Compila#2026 -> utente di test
--   gbianchi / Compila#2026 -> utente di test
--   lverdi  / Compila#2026 -> utente di test
--   sneri   / Compila#2026 -> utente di test SENZA profilo (lo completa al primo accesso)
--
-- I profili sono diversi tra loro per provare i destinatari dei questionari
-- (eta calcolate a novembre 2026):
--   mrossi  Mario Rossi     uomo,  28 anni (under 30), Nord,       studente
--   gbianchi Giulia Bianchi  donna, 38 anni (30-50),    Centro,     lavoratrice
--   lverdi  Luca Verdi      uomo,  61 anni (over 50),  Sud e isole, pensionato
--   sneri   Sara Neri       profilo da completare
--
-- Le password sono salvate come hash bcrypt (generati con password_hash di PHP)
-- =====================================================================

USE questionari_pw;

INSERT INTO utenti (username, password_hash, nome, ruolo, sesso, data_nascita, area_geografica, occupazione) VALUES
  ('gestore',   '$2y$10$fBjfyVMBFoLkfXEJl4lrhOHFGUnFZwULveDxyjwmnZLa5akRJejPW', 'Amministratore', 'ADMIN',  NULL,    NULL,         NULL,        NULL),
  ('mrossi', '$2y$10$1E6nLxfLOac5j9aZQcP2CeNySZ0MWodRb4iedfVIFYZwRRWeGBcYq', 'Mario Rossi',    'UTENTE', 'UOMO',  '1998-04-12', 'NORD',      'STUDENTE'),
  ('gbianchi', '$2y$10$1E6nLxfLOac5j9aZQcP2CeNySZ0MWodRb4iedfVIFYZwRRWeGBcYq', 'Giulia Bianchi', 'UTENTE', 'DONNA', '1988-09-23', 'CENTRO',    'LAVORATORE'),
  ('lverdi', '$2y$10$1E6nLxfLOac5j9aZQcP2CeNySZ0MWodRb4iedfVIFYZwRRWeGBcYq', 'Luca Verdi',     'UTENTE', 'UOMO',  '1965-02-07', 'SUD_ISOLE', 'PENSIONATO'),
  ('sneri', '$2y$10$1E6nLxfLOac5j9aZQcP2CeNySZ0MWodRb4iedfVIFYZwRRWeGBcYq', 'Sara Neri',      'UTENTE', NULL,    NULL,         NULL,        NULL);
