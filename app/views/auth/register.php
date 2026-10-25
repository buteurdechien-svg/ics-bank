<div class="container-narrow">
  <h1>Ouvrir un compte</h1>
  <p class="muted">Créez votre compte ICS Bank en moins d'une minute. Un code de vérification vous sera envoyé.</p>

  <div class="card">
    <?php foreach ($errors as $err): ?>
      <div class="alert alert-error"><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="post" action="/register">
      <?= csrf_field() ?>
      <div class="field">
        <label for="full_name">Nom complet</label>
        <input type="text" id="full_name" name="full_name" value="<?= old_input('full_name') ?>" required>
      </div>
      <div class="field">
        <label for="login">Identifiant (e-mail)</label>
        <input type="email" id="login" name="login" value="<?= old_input('login') ?>" placeholder="prenom@exemple.com" required>
      </div>
      <div class="field">
        <label for="phone">Numéro de téléphone</label>
        <input type="tel" id="phone" name="phone" value="<?= old_input('phone') ?>" placeholder="+225 07 00 00 00 00" required>
        <div class="help">Numéro fictif accepté pour la formation.</div>
      </div>
      <div class="field">
        <label for="secret">Code secret</label>
        <input type="password" id="secret" name="secret" minlength="6" required>
        <div class="help">Au moins 6 caractères.</div>
      </div>
      <div class="field">
        <label for="secret2">Confirmer le code secret</label>
        <input type="password" id="secret2" name="secret2" minlength="6" required>
      </div>
      <button type="submit" class="btn btn-block">Recevoir mon code</button>
    </form>
  </div>
  <p class="text-center muted">Déjà client ? <a href="/login">Se connecter</a></p>
</div>
<?php unset($_SESSION['_old']); ?>
