<?php
/**
 * ICS Bank — point d'entrée unique (routeur).
 * Apache redirige toutes les URL ici (FallbackResource / .htaccess).
 */
declare(strict_types=1);

require dirname(__DIR__) . '/app/lib/helpers.php';
require dirname(__DIR__) . '/app/lib/Mailer.php';

start_session();

$uri    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$uri    = rtrim($uri, '/') ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

/* ─── Les routes /api/v1/* sont traitées par un contrôleur dédié ─── */
if (str_starts_with($uri, '/api/')) {
    require dirname(__DIR__) . '/app/api.php';
    exit;
}

/* ─── Table de routage des pages ─── */
$routes = [
    'GET /'            => 'public/home.php',
    'GET /pricing'     => 'public/pricing.php',
    'GET /register'    => 'auth/register.php',
    'POST /register'   => 'auth/register.php',
    'GET /verify'      => 'auth/verify.php',
    'POST /verify'     => 'auth/verify.php',
    'GET /login'       => 'auth/login.php',
    'POST /login'      => 'auth/login.php',
    'GET /logout'      => 'auth/logout.php',
    'GET /forgot'      => 'auth/forgot.php',
    'POST /forgot'     => 'auth/forgot.php',

    'GET /dashboard'   => 'client/dashboard.php',
    'GET /transfer'    => 'client/transfer.php',
    'POST /transfer'   => 'client/transfer.php',
    'GET /pay'         => 'client/pay.php',
    'POST /pay'        => 'client/pay.php',
    'GET /beneficiaries'  => 'client/beneficiaries.php',
    'POST /beneficiaries' => 'client/beneficiaries.php',
    'GET /history'     => 'client/history.php',
    'GET /complaints'  => 'client/complaints.php',
    'POST /complaints' => 'client/complaints.php',
    'GET /profile'     => 'client/profile.php',
    'POST /profile'    => 'client/profile.php',

    'GET /staff'       => 'pro/staff.php',
    'POST /staff'      => 'pro/staff.php',
];

$key = $method . ' ' . $uri;

if (isset($routes[$key])) {
    require dirname(__DIR__) . '/app/controllers/' . $routes[$key];
    exit;
}

/* ─── 404 ─── */
http_response_code(404);
render('public/error', ['code' => 404, 'message' => 'Page introuvable.', 'title' => 'Introuvable']);
