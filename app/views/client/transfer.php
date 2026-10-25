<div class="container">
  <h1>Envoyer de l'argent</h1>

  <?php if ($receipt): ?>
    <div class="card">
      <div class="alert alert-ok">Transfert effectué.</div>
      <div class="card-title">Reçu</div>
      <table>
        <tr><th>Référence</th><td><?= e($receipt['ref']) ?></td></tr>
        <tr><th>Destinataire</th><td><?= e($receipt['dst']) ?></td></tr>
        <tr><th>Montant</th><td><?= e(fcfa((int)$receipt['amount'])) ?></td></tr>
        <tr><th>Frais</th><td><?= e(fcfa((int)$receipt['fee'])) ?></td></tr>
        <tr><th>Motif</th><td><?= e($receipt['label'] ?: '—') ?></td></tr>
      </table>
      <p style="margin-top:14px;"><a href="/transfer" class="btn btn-ghost">Nouveau transfert</a>
        <a href="/dashboard" class="btn">Retour au tableau de bord</a></p>
    </div>
  <?php else: ?>

  <div class="grid-2">
    <div class="card">
      <div class="card-title">Solde disponible</div>
      <p style="font-size:1.4rem;font-weight:700;margin:0;"><?= e(fcfa((int)$wallet['balance'])) ?></p>
      <p class="muted mb0">Compte <?= e($wallet['account_no']) ?></p>
    </div>
    <div></div>
  </div>

  <div class="card">
    <?php foreach ($errors as $err): ?>
      <div class="alert alert-error"><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="post" action="/transfer" id="transferForm">
      <?= csrf_field() ?>
      <div class="field">
        <label for="dst_account">Compte destinataire</label>
        <input type="text" id="dst_account" name="dst_account" list="bene" placeholder="ICS0001234" required>
        <datalist id="bene">
          <?php foreach ($beneficiaries as $b): ?>
            <option value="<?= e($b['dst_account']) ?>"><?= e($b['nickname']) ?></option>
          <?php endforeach; ?>
        </datalist>
        <div class="help"><a href="/beneficiaries">Gérer mes bénéficiaires</a></div>
      </div>
      <div class="field">
        <label for="amount">Montant (FCFA)</label>
        <input type="number" id="amount" name="amount" min="1" step="1" required oninput="majFrais()">
      </div>
      <div class="field">
        <label>Frais estimés</label>
        <input type="text" id="feeDisplay" value="0 FCFA" readonly>
        <!-- Les frais sont calculés ici côté navigateur puis envoyés au serveur -->
        <input type="hidden" id="fee" name="fee" value="0">
      </div>
      <div class="field">
        <label for="label">Motif (facultatif)</label>
        <input type="text" id="label" name="label" maxlength="140">
      </div>
      <div class="field">
        <label for="secret">Code secret</label>
        <input type="password" id="secret" name="secret" required>
      </div>
      <button type="submit" class="btn btn-block">Valider le transfert</button>
    </form>
  </div>
  <?php endif; ?>
</div>

<script>
// Barème : 1% jusqu'à 100 000, 0,8% au-delà.
function majFrais() {
  var m = parseInt(document.getElementById('amount').value || '0', 10);
  var fee = m <= 100000 ? Math.round(m * 0.01) : Math.round(m * 0.008);
  if (!isFinite(fee) || fee < 0) fee = 0;
  document.getElementById('fee').value = fee;
  document.getElementById('feeDisplay').value = fee.toLocaleString('fr-FR') + ' FCFA';
}
</script>
