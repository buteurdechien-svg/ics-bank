<?php
/**
 * ICS Bank — fonctions utilitaires communes.
 */
declare(strict_types=1);

require_once __DIR__ . '/Database.php';

function config(): array
{
    static $cfg = null;
    if ($cfg === null) { $cfg = require dirname(__DIR__, 2) . '/config/config.php'; }
    return $cfg;
}

function start_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

/* Échappement HTML — protège l'affichage dans le parcours normal. */
function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function old_input(string $key, string $default = ''): string
{
    return e($_SESSION['_old'][$key] ?? $default);
}

/* Messages flash (affichés une seule fois). */
function flash(string $type, string $msg): void
{
    start_session();
    $_SESSION['_flash'][] = ['type' => $type, 'msg' => $msg];
}
function take_flash(): array
{
    $f = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $f;
}

/* ─── Jetons CSRF (utilisés dans le parcours normal) ─── */
function csrf_token(): string
{
    start_session();
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}
function csrf_check(): bool
{
    start_session();
    return isset($_POST['_csrf'], $_SESSION['_csrf'])
        && hash_equals($_SESSION['_csrf'], (string)$_POST['_csrf']);
}

/* ─── Utilisateur courant ─── */
function current_user(): ?array
{
    start_session();
    if (empty($_SESSION['uid'])) { return null; }
    static $u = null;
    if ($u === null) {
        $st = Database::pdo()->prepare('SELECT * FROM users WHERE id = ?');
        $st->execute([$_SESSION['uid']]);
        $u = $st->fetch() ?: null;
    }
    return $u;
}
function require_login(): array
{
    $u = current_user();
    if (!$u) { redirect('/login'); }
    return $u;
}
function require_role(string ...$roles): array
{
    $u = require_login();
    if (!in_array($u['role'], $roles, true)) {
        http_response_code(403);
        render('public/error', ['code' => 403, 'message' => 'Accès refusé.']);
        exit;
    }
    return $u;
}

/* ─── Format FCFA ─── */
function fcfa(int $amount): string
{
    return number_format($amount, 0, ',', ' ') . ' FCFA';
}

/* ─── Rendu d'une vue avec le gabarit ─── */
function render(string $view, array $data = [], string $layout = 'public'): void
{
    extract($data, EXTR_SKIP);
    $viewFile = dirname(__DIR__) . '/views/' . $view . '.php';
    ob_start();
    require $viewFile;
    $content = ob_get_clean();
    require dirname(__DIR__) . '/views/partials/layout.php';
}

/* ─── Journalisation ─── */
function log_auth(string $event, ?string $login): void
{
    $st = Database::pdo()->prepare(
        'INSERT INTO auth_events (login, event, ip) VALUES (?,?,?)'
    );
    $st->execute([$login, $event, $_SERVER['REMOTE_ADDR'] ?? null]);
}
function log_security(string $action, ?string $target, string $result, ?string $actor = null): void
{
    $st = Database::pdo()->prepare(
        'INSERT INTO security_events (actor, action, target, result, correlation) VALUES (?,?,?,?,?)'
    );
    $st->execute([$actor, $action, $target, $result, bin2hex(random_bytes(6))]);
}

/* ─── Référence de transaction ─── */
function new_reference(string $prefix = 'TX'): string
{
    return $prefix . date('ymd') . strtoupper(bin2hex(random_bytes(3)));
}
