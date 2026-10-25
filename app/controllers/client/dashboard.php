<?php
/** Tableau de bord client (version socle — enrichie à l'étape suivante). */
$u = require_login();
$pdo = Database::pdo();

$w = $pdo->prepare('SELECT * FROM wallets WHERE user_id = ?');
$w->execute([$u['id']]);
$wallet = $w->fetch();

$tx = $pdo->prepare(
    'SELECT t.*, ws.account_no AS src_acc, wd.account_no AS dst_acc
     FROM transactions t
     LEFT JOIN wallets ws ON ws.id = t.src_wallet
     LEFT JOIN wallets wd ON wd.id = t.dst_wallet
     WHERE t.src_wallet = ? OR t.dst_wallet = ?
     ORDER BY t.created_at DESC LIMIT 8'
);
$tx->execute([$wallet['id'] ?? 0, $wallet['id'] ?? 0]);
$recent = $tx->fetchAll();

render('client/dashboard', [
    'title' => 'Tableau de bord', 'nav' => 'dashboard',
    'user' => $u, 'wallet' => $wallet, 'recent' => $recent,
], 'app');
