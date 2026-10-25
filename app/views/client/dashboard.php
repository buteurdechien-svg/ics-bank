<div class="container">
  <h1>Bonjour, <?= e(explode(' ', $user['full_name'])[0]) ?></h1>

  <div class="grid-2">
    <div class="card balance-card">
      <div class="card-title">Solde disponible</div>
      <div class="balance-amount"><?= e(fcfa((int)($wallet['balance'] ?? 0))) ?></div>
      <div class="balance-account">Compte <?= e($wallet['account_no'] ?? '—') ?> · <?= e($user['phone']) ?></div>
    </div>
    <div class="card">
      <div class="card-title">Actions rapides</div>
      <p><a href="/transfer" class="btn">Envoyer de l'argent</a></p>
      <p><a href="/pay" class="btn btn-ghost">Payer un marchand</a>
         &nbsp; <a href="/beneficiaries" class="btn btn-ghost">Bénéficiaires</a></p>
    </div>
  </div>

  <div class="card">
    <h2>Dernières opérations</h2>
    <?php if (!$recent): ?>
      <p class="muted">Aucune opération pour le moment.</p>
    <?php else: ?>
      <table>
        <thead><tr><th>Date</th><th>Référence</th><th>Motif</th><th style="text-align:right">Montant</th></tr></thead>
        <tbody>
        <?php foreach ($recent as $t): ?>
          <?php $out = ((int)$t['src_wallet'] === (int)$wallet['id']); ?>
          <tr>
            <td class="muted"><?= e(date('d/m/Y', strtotime($t['created_at']))) ?></td>
            <td><?= e($t['reference']) ?></td>
            <td><?= e($t['label'] ?? '—') ?></td>
            <td style="text-align:right" class="<?= $out?'amount-out':'amount-in' ?>">
              <?= $out?'-':'+' ?><?= e(fcfa((int)$t['amount'])) ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
    <p class="mb0"><a href="/history">Voir tout l'historique →</a></p>
  </div>
</div>
