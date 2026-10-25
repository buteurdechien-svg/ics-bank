<?php
/** Routeur pour le serveur intégré de PHP (tests locaux uniquement).
 *  En production (Apache), c'est .htaccess qui gère la réécriture. */
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $path;
if ($path !== '/' && file_exists($file) && !is_dir($file)) {
    return false; // sert le fichier statique tel quel
}
require __DIR__ . '/index.php';
