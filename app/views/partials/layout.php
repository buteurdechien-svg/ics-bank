<?php
/** Gabarit HTML commun. $content, $title, $layout, $user viennent de render(). */
$cfg  = config();
$user = $user ?? current_user();
$nav  = $nav ?? 'public';
?><!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? 'ICS Bank') ?> — ICS Bank</title>
  <link rel="stylesheet" href="/assets/css/fonts.css">
  <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
  <div class="lab-banner">
    ⚠️ BANQUE FICTIVE — Laboratoire de formation au pentest. Données synthétiques. Volontairement vulnérable. Usage local uniquement.
  </div>

  <nav class="nav">
    <a class="brand" href="/">
      <span class="logo">ICS</span>
      <span>ICS Bank</span>
    </a>
    <?php if (!$user): ?>
      <a href="/pricing"<?= $nav==='pricing'?' class="active"':'' ?>>Tarifs</a>
      <span class="spacer"></span>
      <a href="/login">Se connecter</a>
      <a href="/register" class="btn" style="padding:7px 14px;">Ouvrir un compte</a>
    <?php else: ?>
      <a href="/dashboard"<?= $nav==='dashboard'?' class="active"':'' ?>>Tableau de bord</a>
      <a href="/transfer"<?= $nav==='transfer'?' class="active"':'' ?>>Transfert</a>
      <a href="/pay"<?= $nav==='pay'?' class="active"':'' ?>>Payer</a>
      <a href="/history"<?= $nav==='history'?' class="active"':'' ?>>Historique</a>
      <a href="/complaints"<?= $nav==='complaints'?' class="active"':'' ?>>Réclamations</a>
      <?php if ($user['role'] !== 'client'): ?>
        <a href="/staff"<?= $nav==='staff'?' class="active"':'' ?>>Espace <?= e($user['role']) ?></a>
      <?php endif; ?>
      <span class="spacer"></span>
      <span class="badge badge-role" title="Rôle"><?= e($user['full_name']) ?></span>
      <a href="/logout">Déconnexion</a>
    <?php endif; ?>
  </nav>

  <?php foreach (take_flash() as $f): ?>
    <div class="container" style="padding-bottom:0;">
      <div class="alert alert-<?= $f['type']==='error'?'error':($f['type']==='ok'?'ok':'info') ?>"><?= e($f['msg']) ?></div>
    </div>
  <?php endforeach; ?>

  <?= $content ?>

  <div class="footer">
    ICS Bank — Ivoire Cyber Shield · Banque fictive de formation · © <?= date('Y') ?><br>
    Données exclusivement synthétiques. Aucun service bancaire réel.
  </div>
</body>
</html>
