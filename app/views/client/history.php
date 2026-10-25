<div class="container">
  <h1>Historique des opérations</h1>

  <div class="card">
    <form method="get" action="/history" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
      <div class="field" style="flex:1;margin-bottom:0;min-width:220px">
        <label for="q">Rechercher (motif)</label>
        <input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="ex. loyer, projet...">
      </div>
      <button type="submit" class="btn">Rechercher</button>
      <a class="btn btn-ghost" href="/history<?= $q!==''?'?q='.urlencode($q).'&export=csv':'?export=csv' ?>">Export CSV</a>
    </form>
  </div>

  <div class="card">
    <?php if ($dbError): ?>
      <div class="alert alert-error">Erreur lors de la recherche : <?= e($dbError) ?></div>
    <?php endif; ?>

    <?php if (!$rows): ?>
      <p class="muted">Aucune opération<?= $q!=='' ? ' pour « '.e($q).' »' : '' ?>.</p>
    <?php else: ?>
      <table>
        <thead><tr><th>Date</th><th>Référence</th><th>Motif</th><th>Frais</th><th style="text-align:right">Montant</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $t): ?>
          <?php $out = ((int)$t['src_wallet'] === (int)$wallet['id']); ?>
          <tr>
            <td class="muted"><?= e(date('d/m/Y H:i', strtotime($t['created_at']))) ?></td>
            <td><?= e($t['reference']) ?></td>
            <td><?= e($t['label'] ?? '—') ?></td>
            <td class="muted"><?= e(fcfa((int)$t['fee'])) ?></td>
            <td style="text-align:right" class="<?= $out?'amount-out':'amount-in' ?>">
              <?= $out?'-':'+' ?><?= e(fcfa((int)$t['amount'])) ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>
