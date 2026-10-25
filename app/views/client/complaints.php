<div class="container">
  <h1>Réclamations</h1>

  <div class="grid-2">
    <div class="card">
      <h2>Nouvelle réclamation</h2>
      <?php foreach ($errors as $err): ?>
        <div class="alert alert-error"><?= e($err) ?></div>
      <?php endforeach; ?>
      <form method="post" action="/complaints" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="field">
          <label for="subject">Objet</label>
          <input type="text" id="subject" name="subject" maxlength="160" required>
        </div>
        <div class="field">
          <label for="body">Message</label>
          <textarea id="body" name="body" rows="5" required></textarea>
        </div>
        <div class="field">
          <label for="attachment">Pièce jointe (facultatif)</label>
          <input type="file" id="attachment" name="attachment">
          <div class="help">PNG, JPG, PDF ou TXT — 5 Mo maximum.</div>
        </div>
        <button type="submit" class="btn btn-block">Envoyer</button>
      </form>
    </div>

    <div class="card">
      <h2>Mes réclamations</h2>
      <?php if (!$complaints): ?>
        <p class="muted">Aucune réclamation.</p>
      <?php else: ?>
        <?php foreach ($complaints as $c): ?>
          <div style="border-bottom:1px solid var(--line);padding:10px 0">
            <strong><?= e($c['subject']) ?></strong>
            <span class="badge badge-role" style="float:right"><?= e($c['status']) ?></span>
            <!-- Affichage côté client : échappé (sain) -->
            <p class="muted mb0" style="margin-top:6px"><?= e($c['body']) ?></p>
            <span class="muted" style="font-size:.8rem"><?= e(date('d/m/Y H:i', strtotime($c['created_at']))) ?></span>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
