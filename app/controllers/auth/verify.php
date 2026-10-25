<?php
/** Vérification de l'OTP d'inscription puis création effective du compte. */
$pdo = Database::pdo();
$errors = [];

$pending = $_SESSION['pending_signup'] ?? null;
if (!$pending) {
    flash('error', "Aucune inscription en attente. Recommencez.");
    redirect('/register');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code'] ?? '');
    if (!csrf_check()) { $errors[] = "Session expirée, réessayez."; }

    if (!$errors) {
        $st = $pdo->prepare(
            'SELECT * FROM otp_challenges
             WHERE phone = ? AND purpose = "signup" AND consumed = 0
             ORDER BY id DESC LIMIT 1'
        );
        $st->execute([$pending['phone']]);
        $otp = $st->fetch();

        if (!$otp) {
            $errors[] = "Code introuvable. Recommencez l'inscription.";
        } elseif (strtotime($otp['expires_at']) < time()) {
            $errors[] = "Code expiré. Recommencez l'inscription.";
        } elseif ($otp['attempts'] >= 5) {
            $errors[] = "Trop de tentatives. Recommencez l'inscription.";
        } elseif (!hash_equals($otp['code'], $code)) {
            $pdo->prepare('UPDATE otp_challenges SET attempts = attempts + 1 WHERE id = ?')
                ->execute([$otp['id']]);
            $errors[] = "Code incorrect.";
        } else {
            // Succès : consommer l'OTP et créer le compte + portefeuille
            $pdo->prepare('UPDATE otp_challenges SET consumed = 1 WHERE id = ?')->execute([$otp['id']]);
            $pdo->beginTransaction();
            try {
                $pdo->prepare(
                    'INSERT INTO users (full_name, login, phone, secret_hash, role) VALUES (?,?,?,?,"client")'
                )->execute([$pending['full_name'], $pending['login'], $pending['phone'], $pending['secret_hash']]);
                $uid = (int)$pdo->lastInsertId();
                $accountNo = 'ICS' . str_pad((string)(2000 + $uid), 7, '0', STR_PAD_LEFT);
                $pdo->prepare('INSERT INTO wallets (user_id, account_no, balance) VALUES (?,?,0)')
                    ->execute([$uid, $accountNo]);
                $pdo->commit();
            } catch (Throwable $ex) {
                $pdo->rollBack();
                $errors[] = "Création impossible. Réessayez.";
            }

            if (!$errors) {
                unset($_SESSION['pending_signup']);
                log_auth('signup_ok', $pending['login']);
                flash('ok', "Compte créé ! Vous pouvez maintenant vous connecter.");
                redirect('/login');
            }
        }
    }
}

render('auth/verify', ['title' => 'Vérification', 'errors' => $errors, 'phone' => $pending['phone']], 'public');
