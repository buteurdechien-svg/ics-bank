<div class="container-narrow">
  <h1>Code secret oublié</h1>
  <p class="muted">Saisissez votre identifiant pour recevoir un code de réinitialisation.</p>

  <div class="card">
    <?php foreach ($errors as $err): ?>
      <div class="alert alert-error"><?= e($err) ?></div>
    <?php endforeach; ?>
    <?php if ($info): ?>
      <div class="alert alert-ok"><?= e($info) ?></div>
    <?php endif; ?>

    <form method="post" action="/forgot">
      <?= csrf_field() ?>
      <div class="field">
        <label for="login">Identifiant (e-mail)</label>
        <input type="email" id="login" name="login" required autofocus>
      </div>
      <button type="submit" class="btn btn-block">Recevoir un code</button>
    </form>
  </div>
  <p class="text-center muted"><a href="/login">Retour à la connexion</a></p>
</div>
