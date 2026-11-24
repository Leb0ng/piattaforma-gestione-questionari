<?php
// ---------------------------------------------------------------
// Configurazione del Back Office
// Se MySQL gira su un'altra porta o con altre credenziali
// si cambia SOLO qui.
// ---------------------------------------------------------------
return [
    'db' => [
        'host'     => '127.0.0.1',
        'porta'    => 8889,              // porta di MySQL in MAMP (quella standard sarebbe 3306)
        'nome'     => 'questionari_pw',
        'utente'   => 'root',
        'password' => 'root',
    ],
    'nome_app' => 'Questionari',
];
