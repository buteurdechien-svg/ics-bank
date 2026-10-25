<?php
/** Espaces professionnels : agent, marchand, support, administrateur. */
$u = require_login();           // volontairement : require_login et non require_role
$pdo = Database::pdo();
$errors = [];
$notice = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    /* ── Agent : dépôt d'espèces sur un compte client (cash-in) ── */
    if ($action === 'cashin') {
        // [LAB · Défi 04 — contrôle d'accès fonctionnel manquant]
        // Cette action devrait être réservée au rôle 'agent', mais aucune
        // vérification de rôle n'est faite : tout utilisateur connecté peut
        // créditer un compte.
        $acc    = trim($_POST['account_no'] ?? '');
        $amount = (int)($_POST['amount'] ?? 0);
        if ($amount <= 0) {
            $errors[] = "Montant invalide.";
        } else {
            $w = $pdo->prepare('SELECT id FROM wallets WHERE account_no = ?');
            $w->execute([$acc]);
            $wid = $w->fetchColumn();
            if (!$wid) {
                $errors[] = "Compte introuvable.";
            } else {
                $pdo->prepare('UPDATE wallets SET balance = balance + ? WHERE id = ?')->execute([$amount, $wid]);
                $pdo->prepare(
                    'INSERT INTO transactions (reference, src_wallet, dst_wallet, amount, type, label)
                     VALUES (?,NULL,?,?,"deposit","Depot especes (agent)")'
                )->execute([new_reference('DP'), $wid, $amount]);
                log_security('cashin', $acc, 'done', $u['login']);
                $notice = "Dépôt de " . fcfa($amount) . " effectué sur $acc.";
            }
        }
    }

    /* ── Support : répondre à une réclamation ── */
    elseif ($action === 'answer') {
        $cid = (int)($_POST['complaint_id'] ?? 0);
        $pdo->prepare('UPDATE complaints SET status = "answered" WHERE id = ?')->execute([$cid]);
        $notice = "Réclamation marquée comme traitée.";
    }

    /* ── Changement de rôle d'un utilisateur ── */
    elseif ($action === 'setrole') {
        // [LAB · Défi 14 — élévation de privilèges]
        // Action d'administration (changer le rôle d'un compte) accessible sans
        // vérifier que l'acteur est 'admin' : un compte 'support' peut l'invoquer
        // et se promouvoir, ou promouvoir un complice.
        $uid  = (int)($_POST['user_id'] ?? 0);
        $role = $_POST['role'] ?? 'client';
        if (in_array($role, ['client','agent','merchant','support','admin'], true)) {
            $pdo->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$role, $uid]);
            log_security('setrole', "user#$uid=>$role", 'done', $u['login']);
            $notice = "Rôle mis à jour.";
        }
    }

    /* ── Admin : réinitialiser le solde d'un compte de démonstration ── */
    elseif ($action === 'freeze' && $u['role'] === 'admin') {
        $uid = (int)($_POST['user_id'] ?? 0);
        $pdo->prepare('UPDATE wallets SET status = IF(status="active","frozen","active") WHERE user_id = ?')->execute([$uid]);
        $notice = "Statut du portefeuille modifié.";
    }
}

/* Données selon le rôle */
$data = ['title' => 'Espace ' . $u['role'], 'nav' => 'staff', 'user' => $u,
         'errors' => $errors, 'notice' => $notice];

if ($u['role'] === 'support' || $u['role'] === 'admin') {
    // Réclamations (affichées côté support SANS échappement -> XSS stocké défi 07)
    $data['complaints'] = $pdo->query(
        'SELECT c.*, u.full_name, u.login FROM complaints c JOIN users u ON u.id = c.owner_id ORDER BY c.created_at DESC'
    )->fetchAll();
}
if ($u['role'] === 'admin') {
    $data['users'] = $pdo->query(
        'SELECT u.id, u.full_name, u.login, u.role, u.status, w.account_no, w.balance
         FROM users u LEFT JOIN wallets w ON w.user_id = u.id ORDER BY u.id'
    )->fetchAll();
    $data['events'] = $pdo->query('SELECT * FROM security_events ORDER BY id DESC LIMIT 20')->fetchAll();
}
if ($u['role'] === 'merchant') {
    $m = $pdo->prepare('SELECT * FROM merchants WHERE user_id = ?');
    $m->execute([$u['id']]);
    $merchant = $m->fetch();
    $data['merchant'] = $merchant;
    if ($merchant) {
        $p = $pdo->prepare(
            'SELECT mp.*, w.account_no AS payer FROM merchant_payments mp
             LEFT JOIN wallets w ON w.id = mp.payer_wallet WHERE mp.merchant_id = ? ORDER BY mp.created_at DESC'
        );
        $p->execute([$merchant['id']]);
        $data['payments'] = $p->fetchAll();
    } else {
        $data['payments'] = [];
    }
}
if ($u['role'] === 'agent') {
    $data['recent'] = $pdo->query(
        'SELECT t.reference, t.amount, t.created_at, w.account_no
         FROM transactions t JOIN wallets w ON w.id = t.dst_wallet
         WHERE t.type = "deposit" ORDER BY t.id DESC LIMIT 10'
    )->fetchAll();
}

render('pro/staff', $data, 'app');
