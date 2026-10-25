<div class="container-narrow">
  <h1>Vérification</h1>
  <p class="muted">Un code à 6 chiffres a été envoyé pour le numéro <strong><?= e($phone) ?></strong>.
     Dans ce laboratoire, retrouvez-le dans <strong>Mailpit</strong> (http://localhost:8025).</p>

  <div class="card">
    <?php foreach ($errors as $err): ?>
      <div class="alert alert-error"><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="post" action="/verify">
      <?= csrf_field() ?>
      <div class="field">
        <label for="code">Code de vérification</label>
        <input type="text" id="code" name="code" inputmode="numeric" pattern="[0-9]*" maxlength="6" required autofocus>
      </div>
      <button type="submit" class="btn btn-block">Valider</button>
    </form>
  </div>
  <p class="text-center muted"><a href="/register">Recommencer l'inscription</a></p>
</div>
