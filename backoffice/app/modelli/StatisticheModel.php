<?php
declare(strict_types=1);

// Statistiche di un questionario pubblicato o chiuso: solo letture.
// Le risposte le prendo con poche query e poi le conto in PHP, perche' i numeri
// in gioco sono piccoli e cosi la fascia d'eta la calcolo con la stessa funzione
// fasciaEta() usata nel resto del Back Office (stessi confini delle API).
// NB: il profilo usato per i gruppi e' quello ATTUALE dell'utente, non quello
// che aveva quando ha risposto (nel db non ne salvo una copia).
final class StatisticheModel
{
    // numeri generali: quante compilazioni e da quale canale
    public static function riepilogo(int $questionarioId): array
    {
        $query = Database::connessione()->prepare(
            "SELECT COUNT(*) AS totale,
                    COALESCE(SUM(canale = 'WEB'), 0) AS web,
                    COALESCE(SUM(canale = 'MOBILE'), 0) AS mobile
               FROM compilazioni WHERE questionario_id = ?"
        );
        $query->execute([$questionarioId]);
        return array_map('intval', $query->fetch());
    }

    // Per ogni domanda (stesso ordine di DomandaModel::perQuestionario):
    //   rispondenti = quante compilazioni hanno risposto a quella domanda
    //   opzioni     = per ogni opzione quante volte e' stata scelta e la percentuale
    //   gruppi      = per sesso, fascia d'eta, area e occupazione: l'opzione piu scelta
    //   testi       = per le domande aperte, le ultime risposte scritte
    public static function perDomanda(int $questionarioId, array $domande): array
    {
        // tutte le risposte del questionario con il profilo di chi le ha date
        $query = Database::connessione()->prepare(
            'SELECT r.compilazione_id, r.domanda_id, r.opzione_id, r.testo,
                    u.sesso, u.data_nascita, u.area_geografica, u.occupazione
               FROM risposte r
               JOIN compilazioni c ON c.id = r.compilazione_id
               JOIN utenti u ON u.id = c.utente_id
              WHERE c.questionario_id = ?
              ORDER BY c.inviata_il DESC, r.id'
        );
        $query->execute([$questionarioId]);
        $righe = $query->fetchAll();

        // raggruppo le righe per domanda
        $perDomanda = [];
        foreach ($righe as $riga) {
            $perDomanda[(int) $riga['domanda_id']][] = $riga;
        }

        $risultato = [];
        foreach ($domande as $domanda) {
            $id = (int) $domanda['id'];
            $mie = $perDomanda[$id] ?? [];
            $rispondenti = count(array_unique(array_column($mie, 'compilazione_id')));

            $stat = [
                'id'          => $id,
                'testo'       => $domanda['testo'],
                'tipologia'   => $domanda['tipologia'],
                'rispondenti' => $rispondenti,
                'opzioni'     => [],
                'gruppi'      => [],
                'testi'       => [],
            ];

            if ($domanda['tipologia'] === 'APERTA') {
                // le piu recenti per prime (ORDER BY della query)
                $stat['testi'] = array_slice(array_column($mie, 'testo'), 0, 3);
                $risultato[] = $stat;
                continue;
            }

            // quante volte e' stata scelta ogni opzione
            $conteggi = array_count_values(array_map('intval', array_column($mie, 'opzione_id')));
            $massimo = $conteggi ? max($conteggi) : 0;
            foreach ($domanda['opzioni'] as $opzione) {
                $n = $conteggi[(int) $opzione['id']] ?? 0;
                $stat['opzioni'][] = [
                    'testo' => $opzione['testo'],
                    'n'     => $n,
                    // percentuale sulle persone che hanno risposto alla domanda:
                    // nella multipla la somma puo superare 100 (piu scelte a testa)
                    'perc'  => $rispondenti > 0 ? (int) round($n / $rispondenti * 100) : 0,
                    'top'   => $n > 0 && $n === $massimo,
                ];
            }

            $stat['gruppi'] = self::perGruppi($mie, $domanda['opzioni']);
            $risultato[] = $stat;
        }
        return $risultato;
    }

    // Per ogni categoria del profilo e ogni suo valore: quante persone del gruppo
    // hanno risposto e qual e' l'opzione che hanno scelto di piu (a pari merito: tutte).
    // Chi non ha indicato quel dato nel profilo non entra in quella categoria.
    private static function perGruppi(array $righe, array $opzioni): array
    {
        $testoOpzione = array_column($opzioni, 'testo', 'id');
        $gruppi = [];

        foreach (categorie() as $chiave => $categoria) {
            $conta = [];       // valore => [opzioneId => volte]
            $persone = [];     // valore => [compilazioneId => true]
            foreach ($righe as $riga) {
                $valore = $chiave === 'eta'
                    ? fasciaEta($riga['data_nascita'])
                    : $riga[$categoria['colonna_utente']];
                if ($valore === null) {
                    continue;
                }
                $conta[$valore][(int) $riga['opzione_id']] = ($conta[$valore][(int) $riga['opzione_id']] ?? 0) + 1;
                $persone[$valore][(int) $riga['compilazione_id']] = true;
            }

            // nell'ordine dell'elenco dei valori (Uomo, Donna, Altro...), non in quello casuale dei dati
            foreach ($categoria['valori'] as $valore => $etichetta) {
                if (empty($conta[$valore])) {
                    continue;
                }
                $quanti = count($persone[$valore]);
                $max = max($conta[$valore]);
                $migliori = array_keys(array_filter($conta[$valore], fn($n) => $n === $max));
                $gruppi[] = [
                    'categoria'   => $categoria['etichetta'],
                    'valore'      => $etichetta,
                    'rispondenti' => $quanti,
                    'scelta'      => implode(' / ', array_map(fn($id) => $testoOpzione[$id] ?? '?', $migliori)),
                    'perc'        => (int) round($max / $quanti * 100),
                    'parita'      => count($migliori) > 1,
                ];
            }
        }
        return $gruppi;
    }
}
