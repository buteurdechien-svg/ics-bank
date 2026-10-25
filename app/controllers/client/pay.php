<?php
/** Paiement marchand. */
$u = require_login();
$pdo = Database::pdo();

$w = $pdo->prepare('SELECT * FROM wallets WHERE user_id = ?');
$w->execute([$u['id']]);
$wallet = $w->fetch();

$errors = [];
$receipt = null;
$preview = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'pay';

    if ($action === 'preview') {
        $url = trim($_POST['preview_url'] ?? '');
        if ($url !== '') {
            $ctx = stream_context_create(['http' => ['timeout' => 3]]);
            $data = @file_get_contents($url, false, $ctx);
            $preview = $data === false ? "(aucune réponse du service)" : substr($data, 0, 2000);
        }
    } else {
        $code   = trim($_POST['merchant_code'] ?? '');
        $amount = (int)($_POST['amount'] ?? 0);
        $pin    = $_POST['secret'] ?? '';

        if (!csrf_check())  { $errors[] = "Session expirée, réessayez."; }
        if ($amount <= 0)   { $errors[] = "Montant invalide."; }
        if (!password_verify($pin, $u['secret_hash'])) { $errors[] = "Code secret incorrect."; }

        $merchant = null;
        if (!$errors) {
            $m = $pdo->prepare('SELECT * FROM merchants WHERE code = ?');
            $m->execute([$code]);
            $merchant = $m->fetch();
            if (!$merchant) { $errors[] = "Marchand introuvable."; }
        }

        if (!$errors) {
            if ((int)$wallet['balance'] < $amount) {
                $errors[] = "Solde insuffisant.";
            } else {
                // Paiement : transaction atomique (parcours normal sécurisé).
                $ref = new_reference('PM');
                $pdo->beginTransaction();
                try {
                    $pdo->prepare('UPDATE wallets SET balance = balance - ? WHERE id = ?')
                        ->execute([$amount, $wallet['id']]);
                    if ($merchant['user_id']) {
                        $mw = $pdo->prepare('SELECT id FROM wallets WHERE user_id = ?');
                        $mw->execute([$merchant['user_id']]);
                        if ($mwid = $mw->fetchColumn()) {
                            $pdo->prepare('UPDATE wallets SET balance = balance + ? WHERE id = ?')
                                ->execute([$amount, $mwid]);
                        }
                    }
                    $pdo->prepare(
                        'INSERT INTO merchant_payments (merchant_id, payer_wallet, amount, reference) VALUES (?,?,?,?)'
                    )->execute([$merchant['id'], $wallet['id'], $amount, $ref]);
                    $pdo->prepare(
                        'INSERT INTO transactions (reference, src_wallet, dst_wallet, amount, type, label)
                         VALUES (?,?,NULL,?,"payment",?)'
                    )->execute([$ref, $wallet['id'], $amount, 'Paiement ' . $merchant['name']]);
                    $pdo->commit();
                } catch (Throwable $ex) {
                    $pdo->rollBack();
                    $errors[] = "Paiement impossible.";
                }
                if (!$errors) {
                    $receipt = ['ref' => $ref, 'merchant' => $merchant['name'], 'amount' => $amount];
                    $w->execute([$u['id']]); $wallet = $w->fetch();
                }
            }
        }
    }
}

$mlist = $pdo->query('SELECT code, name FROM merchants ORDER BY name')->fetchAll();

render('client/pay', [
    'title' => 'Payer un marchand', 'nav' => 'pay', 'user' => $u, 'wallet' => $wallet,
    'merchants' => $mlist, 'errors' => $errors, 'receipt' => $receipt, 'preview' => $preview,
], 'app');
