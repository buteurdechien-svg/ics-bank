<?php
/** Historique des opérations, avec recherche et export. */
$u = require_login();
$pdo = Database::pdo();

$w = $pdo->prepare('SELECT * FROM wallets WHERE user_id = ?');
$w->execute([$u['id']]);
$wallet = $w->fetch();
$wid = (int)$wallet['id'];

$q = trim($_GET['q'] ?? '');
$format = $_GET['export'] ?? null;
$rows = [];
$dbError = null;

try {
    if ($q !== '') {
        $sql = "SELECT t.*, ws.account_no AS src_acc, wd.account_no AS dst_acc
                FROM transactions t
                LEFT JOIN wallets ws ON ws.id = t.src_wallet
                LEFT JOIN wallets wd ON wd.id = t.dst_wallet
                WHERE (t.src_wallet = $wid OR t.dst_wallet = $wid)
                  AND t.label LIKE '%$q%'
                ORDER BY t.created_at DESC";
        $rows = $pdo->query($sql)->fetchAll();
    } else {
        $st = $pdo->prepare(
            "SELECT t.*, ws.account_no AS src_acc, wd.account_no AS dst_acc
             FROM transactions t
             LEFT JOIN wallets ws ON ws.id = t.src_wallet
             LEFT JOIN wallets wd ON wd.id = t.dst_wallet
             WHERE t.src_wallet = ? OR t.dst_wallet = ?
             ORDER BY t.created_at DESC"
        );
        $st->execute([$wid, $wid]);
        $rows = $st->fetchAll();
    }
} catch (Throwable $ex) {
    $dbError = $ex->getMessage();
}

/* Export CSV (contrôle de propriété assuré par le filtre sur le portefeuille). */
if ($format === 'csv' && !$dbError) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="releve_' . $wallet['account_no'] . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Date', 'Reference', 'Sens', 'Montant', 'Frais', 'Motif']);
    foreach ($rows as $t) {
        $sens = ((int)$t['src_wallet'] === $wid) ? 'Debit' : 'Credit';
        fputcsv($out, [$t['created_at'], $t['reference'], $sens, $t['amount'], $t['fee'], $t['label']]);
    }
    fclose($out);
    exit;
}

render('client/history', [
    'title' => 'Historique', 'nav' => 'history', 'user' => $u,
    'wallet' => $wallet, 'rows' => $rows, 'q' => $q, 'dbError' => $dbError,
], 'app');
