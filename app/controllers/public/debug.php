<?php
/**
 * Page de diagnostic — laissée en place par erreur.
 * [LAB · Défi 01 — divulgation d'informations]
 * Accessible sans authentification, elle révèle des détails techniques
 * inutiles (versions, chemins, configuration, DSN de base).
 */
header('Content-Type: text/plain; charset=utf-8');
$cfg = config();

echo "ICS Bank — diagnostic interne\n";
echo str_repeat('=', 40) . "\n";
echo 'PHP          : ' . PHP_VERSION . "\n";
echo 'Serveur      : ' . ($_SERVER['SERVER_SOFTWARE'] ?? 'n/a') . "\n";
echo 'OS           : ' . php_uname() . "\n";
echo 'App env      : ' . $cfg['app_env'] . "\n";
echo 'DB DSN       : mysql://' . $cfg['db']['user'] . '@' . $cfg['db']['host'] . ':' . $cfg['db']['port'] . '/' . $cfg['db']['name'] . "\n";
echo 'Mail host    : ' . $cfg['mail']['host'] . ':' . $cfg['mail']['port'] . "\n";
echo 'Storage path : ' . $cfg['storage_path'] . "\n";
echo 'Doc root     : ' . ($_SERVER['DOCUMENT_ROOT'] ?? 'n/a') . "\n";
echo 'Extensions   : ' . implode(', ', array_slice(get_loaded_extensions(), 0, 20)) . "\n";
echo "\nRoutes API connues : /api/v1/login, /api/v1/me, /api/v1/wallets/{id}, /api/v1/wallets/{id}/transactions, /api/v1/profile, /api/v1/beneficiaries\n";
