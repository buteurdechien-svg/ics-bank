<div class="container">
  <h1>Payer un marchand</h1>

  <?php if ($receipt): ?>
    <div class="card">
      <div class="alert alert-ok">Paiement effectué.</div>
      <table>
        <tr><th>Référence</th><td><?= e($receipt['ref']) ?></td></tr>
        <tr><th>Marchand</th><td><?= e($receipt['merchant']) ?></td></tr>
        <tr><th>Montant</th><td><?= e(fcfa((int)$receipt['amount'])) ?></td></tr>
      </table>
      <p style="margin-top:14px"><a href="/pay" class="btn btn-ghost">Nouveau paiement</a>
         <a href="/dashboard" class="btn">Tableau de bord</a></p>
    </div>
  <?php else: ?>

  <div class="grid-2">
    <div class="card">
      <h2>Paiement</h2>
      <?php foreach ($errors as $err): ?>
        <div class="alert alert-error"><?= e($err) ?></div>
      <?php endforeach; ?>
      <p class="muted">Solde : <strong><?= e(fcfa((int)$wallet['balance'])) ?></strong></p>
      <form method="post" action="/pay">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="pay">
        <div class="field">
          <label for="merchant_code">Code marchand</label>
          <input type="text" id="merchant_code" name="merchant_code" list="mlist" placeholder="MARCH01" required>
          <datalist id="mlist">
            <?php foreach ($merchants as $m): ?>
              <option value="<?= e($m['code']) ?>"><?= e($m['name']) ?></option>
            <?php endforeach; ?>
          </datalist>
        </div>
        <div class="field">
          <label for="amount">Montant (FCFA)</label>
          <input type="number" id="amount" name="amount" min="1" required>
        </div>
        <div class="field">
          <label for="secret">Code secret</label>
          <input type="password" id="secret" name="secret" required>
        </div>
        <button type="submit" class="btn btn-block">Payer</button>
      </form>
    </div>

    <div class="card">
      <h2>Aperçu du marchand</h2>
      <p class="muted">Prévisualisez la vitrine ou le logo d'un marchand partenaire à partir de son adresse.</p>
      <form method="post" action="/pay">
        <input type="hidden" name="action" value="preview">
        <div class="field">
          <label for="preview_url">Adresse de la vitrine</label>
          <input type="text" id="preview_url" name="preview_url" placeholder="http://vitrine.marchand.lab/">
        </div>
        <button type="submit" class="btn btn-ghost btn-block">Prévisualiser</button>
      </form>
      <?php if ($preview !== null): ?>
        <div class="card-title" style="margin-top:14px">Réponse</div>
        <pre style="white-space:pre-wrap;background:var(--surface-2);padding:10px;border-radius:8px;max-height:260px;overflow:auto;font-size:.82rem"><?= e($preview) ?></pre>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>
