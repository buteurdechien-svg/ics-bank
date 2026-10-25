<?php
/** Transfert d'argent entre comptes ICS Bank. */
$u = require_login();
$pdo = Database::pdo();

$w = $pdo->prepare('SELECT * FROM wallets WHERE user_id = ?');
$w->execute([$u['id']]);
$wallet = $w->fetch();

$beneStmt = $pdo->prepare('SELECT * FROM beneficiaries WHERE owner_id = ? ORDER BY nickname');
$beneStmt->execute([$u['id']]);
$beneficiaries = $beneStmt->fetchAll();

$errors = [];
$receipt = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dstAccount = trim($_POST['dst_account'] ?? '');
    $amount     = (int)($_POST['amount'] ?? 0);
    // [LAB · Défi 08 — contournement logique du transfert]
    // Les frais sont transmis par le navigateur et acceptés tels quels par
    // le serveur, au lieu d'être recalculés. Combiné à l'absence de contrôle
    // d'un montant strictement positif, cela permet frais nuls et montant négatif.
    $fee        = (int)($_POST['fee'] ?? 0);
    $label      = trim($_POST['label'] ?? '');
    $pin        = $_POST['secret'] ?? '';

    if (!csrf_check()) { $errors[] = "Session expirée, réessayez."; }
    if ($dstAccount === '')          { $errors[] = "Indiquez le compte destinataire."; }
    if ($amount === 0)               { $errors[] = "Indiquez un montant."; }
    if (!password_verify($pin, $u['secret_hash'])) { $errors[] = "Code secret incorrect."; }

    // Destinataire
    $dst = null;
    if (!$errors) {
        $d = $pdo->prepare('SELECT * FROM wallets WHERE account_no = ?');
        $d->execute([$dstAccount]);
        $dst = $d->fetch();
        if (!$dst)                        { $errors[] = "Compte destinataire introuvable."; }
        elseif ((int)$dst['id'] === (int)$wallet['id']) { $errors[] = "Vous ne pouvez pas vous virer à vous-même."; }
    }

    if (!$errors) {
        // [LAB · Défi 11 — intégrité du solde / race condition]
        // Vérification du solde PUIS débit, sans verrou (pas de SELECT ... FOR UPDATE,
        // pas de contrainte d'invariant). Des requêtes concurrentes passent le test
        // en même temps et dépensent plusieurs fois le même solde.
        // Le verrou de session est relâché ici pour que les requêtes concurrentes
        // d'un même client ne soient pas sérialisées (rend la faille atteignable).
        if (session_status() === PHP_SESSION_ACTIVE) { session_write_close(); }
        $total = $amount + $fee;
        if ((int)$wallet['balance'] < $total) {
            $errors[] = "Solde insuffisant.";
        } else {
            usleep(150000); // fenêtre de concurrence (rend la race condition reproductible en lab)
            $ref = new_reference('TX');
            $pdo->prepare('UPDATE wallets SET balance = balance - ? WHERE id = ?')
                ->execute([$total, $wallet['id']]);
            $pdo->prepare('UPDATE wallets SET balance = balance + ? WHERE id = ?')
                ->execute([$amount, $dst['id']]);
            $pdo->prepare(
                'INSERT INTO transactions (reference, src_wallet, dst_wallet, amount, fee, type, label)
                 VALUES (?,?,?,?,?,"transfer",?)'
            )->execute([$ref, $wallet['id'], $dst['id'], $amount, $fee, $label]);

            log_security('transfer', $dstAccount, 'done', $u['login']);
            $receipt = ['ref' => $ref, 'amount' => $amount, 'fee' => $fee, 'dst' => $dstAccount, 'label' => $label];
            // rafraîchir le solde affiché
            $w->execute([$u['id']]); $wallet = $w->fetch();
        }
    }
}

render('client/transfer', [
    'title' => 'Transfert', 'nav' => 'transfer', 'user' => $u,
    'wallet' => $wallet, 'beneficiaries' => $beneficiaries,
    'errors' => $errors, 'receipt' => $receipt,
], 'app');
