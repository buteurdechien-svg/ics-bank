<?php
/** Profil : informations et changement de code secret. */
$u = require_login();
$pdo = Database::pdo();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cur  = $_POST['current'] ?? '';
    $new  = $_POST['new'] ?? '';
    $new2 = $_POST['new2'] ?? '';

    if (!csrf_check())                         { $errors[] = "Session expirée, réessayez."; }
    if (!password_verify($cur, $u['secret_hash'])) { $errors[] = "Code secret actuel incorrect."; }
    if (strlen($new) < 6)                      { $errors[] = "Le nouveau code doit faire au moins 6 caractères."; }
    if ($new !== $new2)                        { $errors[] = "La confirmation ne correspond pas."; }

    if (!$errors) {
        $pdo->prepare('UPDATE users SET secret_hash = ? WHERE id = ?')
            ->execute([password_hash($new, PASSWORD_BCRYPT), $u['id']]);
        flash('ok', "Code secret mis à jour.");
        redirect('/profile');
    }
}

render('client/profile', ['title' => 'Mon profil', 'nav' => '', 'user' => $u, 'errors' => $errors], 'app');
