<?php
declare(strict_types=1);

final class CompilazioniController
{
    // tutte le compilazioni ricevute da un questionario
    public function elenco(): void
    {
        $questionario = QuestionarioModel::trova(idDa($_GET, 'questionario'));
        if ($questionario === null) {
            nonTrovato('Questo questionario non esiste o è stato eliminato.');
        }

        vista('compilazioni/elenco', [
            'questionario' => $questionario,
            'compilazioni' => CompilazioneModel::perQuestionario((int) $questionario['id']),
        ], 'Compilazioni');
    }

    // una compilazione con le risposte date a ogni domanda
    public function dettaglio(): void
    {
        $compilazione = CompilazioneModel::trova(idDa($_GET, 'id'));
        if ($compilazione === null) {
            nonTrovato('Questa compilazione non esiste.');
        }
        $questionarioId = (int) $compilazione['questionario_id'];

        vista('compilazioni/dettaglio', [
            'compilazione' => $compilazione,
            'questionario' => QuestionarioModel::trova($questionarioId),
            'domande'      => DomandaModel::perQuestionario($questionarioId),
            'risposte'     => CompilazioneModel::risposte((int) $compilazione['id']),
        ], 'Compilazione di ' . $compilazione['nome']);
    }
}
