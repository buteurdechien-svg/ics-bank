<?php
/**
 * ICS Bank — API REST /api/v1/.
 * Utilisée par l'interface « mobile web ». Certaines routes portent des
 * failles pédagogiques isolées (IDOR, mass assignment).
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$pdo = Database::pdo();

$uri    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$uri    = rtrim($uri, '/');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

function body_json(): array
{
    $raw = file_get_contents('php://input');
    $j = json_decode($raw ?: '[]', true);
    return is_array($j) ? $j : [];
}
function json_out($data, int $code = 200): never
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/* ─── Authentification par jeton de session applicatif ───
   Jeton = sha1(user_id . secret) stocké en session après /api/v1/login.
   (Session PHP partagée avec le site : l'utilisateur connecté au site l'est pour l'API.) */
function api_user(): ?array
{
    start_session();
    if (empty($_SESSION['uid'])) {
        // Jeton Bearer accepté également
        $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/Bearer\s+(\S+)/', $hdr, $m)) {
            $st = Database::pdo()->prepare('SELECT * FROM users WHERE SHA1(CONCAT(id, "|", secret_hash)) = ?');
            $st->execute([$m[1]]);
            return $st->fetch() ?: null;
        }
        return null;
    }
    $st = Database::pdo()->prepare('SELECT * FROM users WHERE id = ?');
    $st->execute([$_SESSION['uid']]);
    return $st->fetch() ?: null;
}

/* ─────────────────────────── Routes ─────────────────────────── */

// POST /api/v1/login  → renvoie un jeton
if ($uri === '/api/v1/login' && $method === 'POST') {
    $b = body_json();
    $st = $pdo->prepare('SELECT * FROM users WHERE login = ?');
    $st->execute([strtolower(trim($b['login'] ?? ''))]);
    $u = $st->fetch();
    if (!$u || !password_verify($b['secret'] ?? '', $u['secret_hash'])) {
        json_out(['error' => 'invalid_credentials'], 401);
    }
    $_SESSION['uid'] = (int)$u['id'];
    $token = sha1($u['id'] . '|' . $u['secret_hash']);
    json_out(['token' => $token, 'user_id' => (int)$u['id'], 'role' => $u['role']]);
}

// GET /api/v1/me
if ($uri === '/api/v1/me' && $method === 'GET') {
    $u = api_user();
    if (!$u) json_out(['error' => 'unauthenticated'], 401);
    json_out(['id' => (int)$u['id'], 'full_name' => $u['full_name'], 'login' => $u['login'],
              'role' => $u['role'], 'phone' => $u['phone']]);
}

// GET /api/v1/wallets/{id}
if (preg_match('#^/api/v1/wallets/(\d+)$#', $uri, $m) && $method === 'GET') {
    $u = api_user();
    if (!$u) json_out(['error' => 'unauthenticated'], 401);

    // [LAB · Défi 05 — IDOR / BOLA]
    // On renvoie le portefeuille demandé par son id SANS vérifier qu'il
    // appartient à l'utilisateur authentifié.
    $st = $pdo->prepare(
        'SELECT w.id, w.account_no, w.balance, w.currency, u.full_name, u.login
         FROM wallets w JOIN users u ON u.id = w.user_id WHERE w.id = ?'
    );
    $st->execute([(int)$m[1]]);
    $wallet = $st->fetch();
    if (!$wallet) json_out(['error' => 'not_found'], 404);
    json_out($wallet);
}

// GET /api/v1/wallets/{id}/transactions
if (preg_match('#^/api/v1/wallets/(\d+)/transactions$#', $uri, $m) && $method === 'GET') {
    $u = api_user();
    if (!$u) json_out(['error' => 'unauthenticated'], 401);
    // Même absence de contrôle de propriété (prolonge le défi 05).
    $st = $pdo->prepare(
        'SELECT reference, src_wallet, dst_wallet, amount, fee, type, label, created_at
         FROM transactions WHERE src_wallet = ? OR dst_wallet = ? ORDER BY created_at DESC'
    );
    $st->execute([(int)$m[1], (int)$m[1]]);
    json_out(['transactions' => $st->fetchAll()]);
}

// PATCH /api/v1/profile  → met à jour le profil
if ($uri === '/api/v1/profile' && in_array($method, ['PATCH','POST'], true)) {
    $u = api_user();
    if (!$u) json_out(['error' => 'unauthenticated'], 401);
    $b = body_json();

    // [LAB · Défi 12 — mass assignment]
    // Tous les champs reçus sont appliqués tels quels, y compris des champs
    // réservés (role, status) que l'utilisateur ne devrait pas pouvoir modifier.
    $allowedEcho = [];
    $cols = [];
    $vals = [];
    foreach ($b as $k => $v) {
        if (in_array($k, ['full_name','phone','role','status','login'], true)) {
            $cols[] = "$k = ?";
            $vals[] = $v;
            $allowedEcho[$k] = $v;
        }
    }
    if ($cols) {
        $vals[] = $u['id'];
        $pdo->prepare('UPDATE users SET ' . implode(', ', $cols) . ' WHERE id = ?')->execute($vals);
    }
    $st = $pdo->prepare('SELECT id, full_name, login, phone, role, status FROM users WHERE id = ?');
    $st->execute([$u['id']]);
    json_out(['updated' => $allowedEcho, 'user' => $st->fetch()]);
}

// GET /api/v1/beneficiaries
if ($uri === '/api/v1/beneficiaries' && $method === 'GET') {
    $u = api_user();
    if (!$u) json_out(['error' => 'unauthenticated'], 401);
    $st = $pdo->prepare('SELECT id, dst_account, nickname, validated FROM beneficiaries WHERE owner_id = ?');
    $st->execute([$u['id']]);
    json_out(['beneficiaries' => $st->fetchAll()]);
}

/* ─── 404 API ─── */
json_out(['error' => 'not_found', 'path' => $uri], 404);
