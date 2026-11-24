<?php
declare(strict_types=1);

// La prima pagina dopo l'accesso: una panoramica di cosa sta succedendo
// e soprattutto di cosa c'e' da fare (bozze pronte, questionari da chiudere...).
final class DashboardController
{
    public function mostra(): void
    {
        $questionari = QuestionarioModel::tutti();

        $aperti = [];       // pubblicati, con la percentuale di risposte
        $daPubblicare = []; // bozze senza problemi
        $daCompletare = []; // bozze a cui manca qualcosa
        $daFare = [];       // avvisi per l'admin

        foreach ($questionari as $q) {
            $compilazioni = (int) $q['num_compilazioni'];
            $destinatari = (int) $q['num_destinatari'];

            if ($q['stato'] === 'PUBBLICATO') {
                // la percentuale puo superare 100 se dopo la pubblicazione qualcuno ha
                // cambiato profilo e non rientra piu nei destinatari: la blocco a 100
                $q['percentuale'] = $destinatari > 0 ? min(100, (int) round($compilazioni / $destinatari * 100)) : 0;
                $aperti[] = $q;

                if ($destinatari === 0) {
                    $daFare[] = ['tipo' => 'errore', 'questionario' => $q,
                        'testo' => 'Nessun utente rientra nei destinatari: per ora non lo può compilare nessuno.'];
                } elseif ($compilazioni >= $destinatari) {
                    $daFare[] = ['tipo' => 'ok', 'questionario' => $q,
                        'testo' => 'Hanno risposto tutti i destinatari: puoi chiuderlo.'];
                }
            } elseif ($q['stato'] === 'BOZZA') {
                $q['problemi'] = QuestionarioModel::problemiPubblicazione((int) $q['id']);
                if ($q['problemi']) {
                    $daCompletare[] = $q;
                } else {
                    $daPubblicare[] = $q;
                    $daFare[] = ['tipo' => 'info', 'questionario' => $q,
                        'testo' => 'La bozza è completa: puoi pubblicarla.'];
                }
            }
        }

        // i questionari aperti con meno risposte in cima: sono quelli da tenere d'occhio
        usort($aperti, fn($a, $b) => $a['percentuale'] <=> $b['percentuale']);

        vista('dashboard', [
            'riepilogo'         => DashboardModel::riepilogo(),
            'andamento'         => DashboardModel::andamento(14),
            'ultime'            => DashboardModel::ultimeCompilazioni(6),
            'profiliIncompleti' => DashboardModel::profiliIncompleti(5),
            'aperti'            => $aperti,
            'daCompletare'      => $daCompletare,
            'daFare'            => $daFare,
            'numBozze'          => count($daPubblicare) + count($daCompletare),
        ], 'Dashboard');
    }
}
