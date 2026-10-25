<?php
// Service interne de démonstration pour le défi SSRF (défi 13).
// Il n'est accessible QUE depuis la VM (127.0.0.1), jamais exposé.
// Une requête serveur de l'application (via l'aperçu marchand) peut l'atteindre.
header('Content-Type: text/plain; charset=utf-8');
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if ($path === '/secret' || $path === '/internal' || $path === '/') {
    echo "=== ICS Bank — service interne (NE DOIT PAS ÊTRE ACCESSIBLE DE L'EXTÉRIEUR) ===\n";
    echo "internal_api_key: ICS-INTERNAL-9f3a2b7c4d\n";
    echo "db_replica: 10.10.0.5:3306\n";
    echo "note: si vous lisez ceci via l'application, c'est une faille SSRF (defi 13).\n";
} else {
    http_response_code(404);
    echo "not found";
}
