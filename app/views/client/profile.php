<div class="container">
  <h1>Mon profil</h1>
  <div class="grid-2">
    <div class="card">
      <h2>Informations</h2>
      <table>
        <tr><th>Nom</th><td><?= e($user['full_name']) ?></td></tr>
        <tr><th>Identifiant</th><td><?= e($user['login']) ?></td></tr>
        <tr><th>Téléphone</th><td><?= e($user['phone']) ?></td></tr>
        <tr><th>Rôle</th><td><?= e($user['role']) ?></td></tr>
      </table>
    </div>
    <div class="card">
      <h2>Changer de code secret</h2>
      <?php foreach ($errors as $err): ?>
        <div class="alert alert-error"><?= e($err) ?></div>
      <?php endforeach; ?>
      <form method="post" action="/profile">
        <?= csrf_field() ?>
        <div class="field"><label for="current">Code actuel</label>
          <input type="password" id="current" name="current" required></div>
        <div class="field"><label for="new">Nouveau code</label>
          <input type="password" id="new" name="new" minlength="6" required></div>
        <div class="field"><label for="new2">Confirmer</label>
          <input type="password" id="new2" name="new2" minlength="6" required></div>
        <button type="submit" class="btn btn-block">Mettre à jour</button>
      </form>
    </div>
  </div>
</div>
