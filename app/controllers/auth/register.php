<?php
/** Inscription : saisie des informations puis envoi d'un OTP de test. */
$pdo = Database::pdo();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['full_name'] ?? '');
    $login = strtolower(trim($_POST['login'] ?? ''));
    $phone = preg_replace('/\s+/', '', $_POST['phone'] ?? '');
    $pin   = $_POST['secret'] ?? '';
    $pin2  = $_POST['secret2'] ?? '';

    if (!csrf_check())                         { $errors[] = "Session expirée, réessayez."; }
    if (mb_strlen($name) < 3)                  { $errors[] = "Indiquez votre nom complet."; }
    if (!filter_var($login, FILTER_VALIDATE_EMAIL)) { $errors[] = "Identifiant invalide (format e-mail attendu)."; }
    if (!preg_match('/^\+?\d{8,15}$/', $phone)) { $errors[] = "Numéro de téléphone invalide."; }
    if (strlen($pin) < 6)                      { $errors[] = "Le code secret doit faire au moins 6 caractères."; }
    if ($pin !== $pin2)                        { $errors[] = "Les deux codes secrets ne correspondent pas."; }

    // Unicité (parcours normal : on ne révèle pas publiquement l'existence)
    if (!$errors) {
        $st = $pdo->prepare('SELECT COUNT(*) FROM users WHERE login = ? OR phone = ?');
        $st->execute([$login, $phone]);
        if ((int)$st->fetchColumn() > 0) {
            $errors[] = "Impossible de créer le compte avec ces informations.";
        }
    }

    if (!$errors) {
        // On mémorise l'inscription en attente dans la session, puis on envoie l'OTP.
        $code = (string)random_int(100000, 999999);
        $pdo->prepare(
            'INSERT INTO otp_challenges (phone, code, purpose, expires_at) VALUES (?,?,?,?)'
        )->execute([$phone, $code, 'signup', date('Y-m-d H:i:s', time() + 600)]);

        $_SESSION['pending_signup'] = [
            'full_name' => $name, 'login' => $login, 'phone' => $phone,
            'secret_hash' => password_hash($pin, PASSWORD_BCRYPT),
        ];
        Mailer::sendOtp($phone, $code);
        log_auth('otp_sent', $login);
        flash('info', "Un code de vérification a été envoyé. Consultez-le dans Mailpit.");
        redirect('/verify');
    }
}

if ($errors) { $_SESSION['_old'] = $_POST; }
render('auth/register', ['title' => 'Ouvrir un compte', 'errors' => $errors], 'public');
