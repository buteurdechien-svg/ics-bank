<?php
/** Gestion des bénéficiaires. */
$u = require_login();
$pdo = Database::pdo();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'add';

    // [LAB · Défi 09 — CSRF]
    // L'ajout de bénéficiaire ne vérifie PAS le jeton CSRF côté serveur
    // (le champ existe dans le formulaire mais n'est pas contrôlé ici),
    // ce qui permet à une page tierce de forcer l'ajout.
    if ($action === 'add') {
        $dst  = trim($_POST['dst_account'] ?? '');
        $nick = trim($_POST['nickname'] ?? '');
        if ($dst === '') {
            $errors[] = "Indiquez le compte du bénéficiaire.";
        } else {
            $chk = $pdo->prepare('SELECT COUNT(*) FROM wallets WHERE account_no = ?');
            $chk->execute([$dst]);
            if ((int)$chk->fetchColumn() === 0) {
                $errors[] = "Aucun compte ICS Bank ne correspond à ce numéro.";
            } else {
                $pdo->prepare(
                    'INSERT INTO beneficiaries (owner_id, dst_account, nickname, validated) VALUES (?,?,?,1)'
                )->execute([$u['id'], $dst, $nick ?: $dst]);
                flash('ok', "Bénéficiaire ajouté.");
                redirect('/beneficiaries');
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        // suppression limitée au propriétaire (contrôle correct)
        $pdo->prepare('DELETE FROM beneficiaries WHERE id = ? AND owner_id = ?')
            ->execute([$id, $u['id']]);
        flash('ok', "Bénéficiaire supprimé.");
        redirect('/beneficiaries');
    }
}

$list = $pdo->prepare('SELECT * FROM beneficiaries WHERE owner_id = ? ORDER BY nickname');
$list->execute([$u['id']]);

render('client/beneficiaries', [
    'title' => 'Bénéficiaires', 'nav' => 'beneficiaries', 'user' => $u,
    'beneficiaries' => $list->fetchAll(), 'errors' => $errors,
], 'app');
