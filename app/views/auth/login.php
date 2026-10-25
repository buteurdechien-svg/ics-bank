<div class="container-narrow">
  <h1>Se connecter</h1>
  <p class="muted">Accédez à votre compte ICS Bank.</p>

  <div class="card">
    <?php foreach ($errors as $err): ?>
      <div class="alert alert-error"><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="post" action="/login">
      <?= csrf_field() ?>
      <div class="field">
        <label for="login">Identifiant (e-mail)</label>
        <input type="email" id="login" name="login" required autofocus>
      </div>
      <div class="field">
        <label for="secret">Code secret</label>
        <input type="password" id="secret" name="secret" required>
      </div>
      <button type="submit" class="btn btn-block">Se connecter</button>
    </form>
    <p class="text-center muted mb0" style="margin-top:14px;"><a href="/forgot">Code secret oublié ?</a></p>
  </div>
  <p class="text-center muted">Pas encore de compte ? <a href="/register">Ouvrir un compte</a></p>
</div>
