<?php
/**
 * ICS Bank — configuration centrale (laboratoire)
 * Les valeurs par défaut conviennent à l'installation native sur Ubuntu.
 * Elles peuvent être surchargées par des variables d'environnement.
 */
declare(strict_types=1);

return [
    'app_name'   => getenv('APP_NAME') ?: 'ICS Bank',
    'app_env'    => getenv('APP_ENV') ?: 'lab',
    'currency'   => 'FCFA',

    'db' => [
        'host'    => getenv('DB_HOST') ?: '127.0.0.1',
        'port'    => getenv('DB_PORT') ?: '3306',
        'name'    => getenv('DB_NAME') ?: 'ics_bank',
        'user'    => getenv('DB_USER') ?: 'ics_app',
        'pass'    => getenv('DB_PASSWORD') ?: 'ics_app_lab',
        'charset' => 'utf8mb4',
    ],

    'mail' => [
        'host' => getenv('MAIL_HOST') ?: '127.0.0.1',
        'port' => (int)(getenv('MAIL_PORT') ?: 1025),
        'from' => getenv('MAIL_FROM') ?: 'no-reply@ics-bank.local',
    ],

    // Chemins hors de public/ (pièces jointes, journaux, exports)
    'storage_path' => dirname(__DIR__) . '/storage',
];
