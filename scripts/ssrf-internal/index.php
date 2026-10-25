<?php
// Service de métadonnées interne ICS Bank.
// Lié à 127.0.0.1 : réservé aux composants internes, non exposé.
header('Content-Type: text/plain; charset=utf-8');
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if ($path === '/secret' || $path === '/internal' || $path === '/') {
    echo "=== ICS Bank internal metadata service ===\n";
    echo "service: ics-bank-internal\n";
    echo "internal_api_key: ICS-INTERNAL-9f3a2b7c4d\n";
    echo "db_replica: 10.10.0.5:3306\n";
    echo "note: internal use only - do not expose to clients.\n";
} else {
    http_response_code(404);
    echo "not found";
}
