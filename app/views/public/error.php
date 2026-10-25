<div class="container-narrow text-center" style="padding-top:60px;">
  <div class="card">
    <h1 style="font-size:3rem;margin-bottom:0;"><?= e((string)($code ?? 404)) ?></h1>
    <p class="muted"><?= e($message ?? 'Une erreur est survenue.') ?></p>
    <a href="/" class="btn">Retour à l'accueil</a>
  </div>
</div>
