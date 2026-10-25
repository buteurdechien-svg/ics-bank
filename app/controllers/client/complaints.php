<?php
/** Réclamations du client : création et suivi. */
$u = require_login();
$pdo = Database::pdo();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $body    = trim($_POST['body'] ?? '');

    if (!csrf_check())           { $errors[] = "Session expirée, réessayez."; }
    if (mb_strlen($subject) < 3) { $errors[] = "Indiquez l'objet de la réclamation."; }
    if (mb_strlen($body) < 5)    { $errors[] = "Décrivez votre réclamation."; }

    if (!$errors) {
        $pdo->prepare('INSERT INTO complaints (owner_id, subject, body) VALUES (?,?,?)')
            ->execute([$u['id'], $subject, $body]);
        $cid = (int)$pdo->lastInsertId();

        // Pièce jointe : stockage hors de public/, nom interne aléatoire, type/taille limités.
        if (!empty($_FILES['attachment']['name']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $f = $_FILES['attachment'];
            $okTypes = ['image/png','image/jpeg','application/pdf','text/plain'];
            if ($f['size'] <= 5*1024*1024 && in_array(mime_content_type($f['tmp_name']), $okTypes, true)) {
                $stored = bin2hex(random_bytes(16));
                $dir = config()['storage_path'] . '/attachments';
                @mkdir($dir, 0775, true);
                move_uploaded_file($f['tmp_name'], $dir . '/' . $stored);
                $pdo->prepare(
                    'INSERT INTO attachments (complaint_id, stored_name, orig_name, mime, size_bytes)
                     VALUES (?,?,?,?,?)'
                )->execute([$cid, $stored, $f['name'], mime_content_type($dir.'/'.$stored) ?: 'application/octet-stream', $f['size']]);
            } else {
                flash('info', "Pièce jointe ignorée (type ou taille non autorisé).");
            }
        }

        flash('ok', "Réclamation enregistrée. Notre support vous répondra.");
        redirect('/complaints');
    }
}

$list = $pdo->prepare('SELECT * FROM complaints WHERE owner_id = ? ORDER BY created_at DESC');
$list->execute([$u['id']]);

render('client/complaints', [
    'title' => 'Réclamations', 'nav' => 'complaints', 'user' => $u,
    'complaints' => $list->fetchAll(), 'errors' => $errors,
], 'app');
