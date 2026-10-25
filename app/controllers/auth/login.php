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

        if (!$u) {
            log_auth('login_fail_unknown', $login);
            $errors[] = "Aucun compte ne correspond à cet identifiant.";
        } elseif ($u['status'] === 'blocked') {
            $errors[] = "Ce compte est bloqué. Contactez le support.";
        } elseif (!password_verify($pin, $u['secret_hash'])) {
            log_auth('login_fail_badpin', $login);
            $errors[] = "Code secret incorrect.";
        } else {
            $_SESSION['uid'] = (int)$u['id'];
            log_auth('login_ok', $login);
            redirect('/dashboard');
        }
    }
}

render('auth/login', ['title' => 'Connexion', 'errors' => $errors], 'public');
