<?php
/** Connexion. */
$pdo = Database::pdo();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = strtolower(trim($_POST['login'] ?? ''));
    $pin   = $_POST['secret'] ?? '';

    if (!csrf_check()) { $errors[] = "Session expirée, réessayez."; }

    if (!$errors) {
        $st = $pdo->prepare('SELECT * FROM users WHERE login = ?');
        $st->execute([$login]);
        $u = $st->fetch();

        // [LAB · Défi 03 — énumération de comptes]
        // Messages distincts selon que l'identifiant existe ou non, et aucune
        // limitation du nombre d'essais : permet d'énumérer puis de forcer un compte.
        if (!$u) {
            log_auth('login_fail_unknown', $login);
            $errors[] = "Aucun compte ne correspond à cet identifiant.";
        } elseif ($u['status'] === 'blocked') {
            $errors[] = "Ce compte est bloqué. Contactez le support.";
        } elseif (!password_verify($pin, $u['secret_hash'])) {
            log_auth('login_fail_badpin', $login);
            $errors[] = "Code secret incorrect.";
        } else {
            // [LAB · Défi 10 — session faible]
            // L'identifiant de session n'est PAS régénéré après authentification
            // (pas de session_regenerate_id), ce qui ouvre la voie à la fixation de session.
            $_SESSION['uid'] = (int)$u['id'];
            log_auth('login_ok', $login);
            redirect('/dashboard');
        }
    }
}

render('auth/login', ['title' => 'Connexion', 'errors' => $errors], 'public');
