<div class="container">
  <h1>Espace <?= e($user['role']) ?></h1>

  <?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>
  <?php if ($notice): ?><div class="alert alert-ok"><?= e($notice) ?></div><?php endif; ?>

  <?php /* ─────────────── AGENT ─────────────── */ if ($user['role'] === 'agent'): ?>
    <div class="card">
      <h2>Dépôt d'espèces (cash-in)</h2>
      <form method="post" action="/staff">
        <input type="hidden" name="action" value="cashin">
        <div class="field"><label>Compte client</label>
          <input type="text" name="account_no" placeholder="ICS0001234" required></div>
        <div class="field"><label>Montant (FCFA)</label>
          <input type="number" name="amount" min="1" required></div>
        <button class="btn">Créditer le compte</button>
      </form>
    </div>
    <div class="card">
      <h2>Derniers dépôts</h2>
      <table><thead><tr><th>Date</th><th>Référence</th><th>Compte</th><th style="text-align:right">Montant</th></tr></thead><tbody>
      <?php foreach ($recent as $r): ?>
        <tr><td class="muted"><?= e(date('d/m/Y H:i', strtotime($r['created_at']))) ?></td>
            <td><?= e($r['reference']) ?></td><td><?= e($r['account_no']) ?></td>
            <td style="text-align:right"><?= e(fcfa((int)$r['amount'])) ?></td></tr>
      <?php endforeach; ?>
      </tbody></table>
    </div>

  <?php /* ─────────────── MARCHAND ─────────────── */ elseif ($user['role'] === 'merchant'): ?>
    <div class="card">
      <h2><?= e($merchant['name'] ?? 'Mon commerce') ?></h2>
      <p class="muted">Code marchand : <strong><?= e($merchant['code'] ?? '—') ?></strong></p>
    </div>
    <div class="card">
      <h2>Mes encaissements</h2>
      <?php if (!$payments): ?><p class="muted">Aucun paiement reçu.</p><?php else: ?>
      <table><thead><tr><th>Date</th><th>Référence</th><th>Payeur</th><th style="text-align:right">Montant</th></tr></thead><tbody>
      <?php foreach ($payments as $p): ?>
        <tr><td class="muted"><?= e(date('d/m/Y H:i', strtotime($p['created_at']))) ?></td>
            <td><?= e($p['reference']) ?></td><td><?= e($p['payer'] ?? '—') ?></td>
            <td style="text-align:right" class="amount-in"><?= e(fcfa((int)$p['amount'])) ?></td></tr>
      <?php endforeach; ?>
      </tbody></table>
      <?php endif; ?>
    </div>

  <?php /* ─────────────── SUPPORT / ADMIN : réclamations ─────────────── */ endif; ?>

  <?php if (in_array($user['role'], ['support','admin'], true)): ?>
    <div class="card">
      <h2>Réclamations clients</h2>
      <?php if (!$complaints): ?><p class="muted">Aucune réclamation.</p><?php else: ?>
        <?php foreach ($complaints as $c): ?>
          <div style="border-bottom:1px solid var(--line);padding:12px 0">
            <strong><?= e($c['subject']) ?></strong>
            <span class="badge badge-role" style="float:right"><?= e($c['status']) ?></span>
            <div class="muted" style="font-size:.82rem">De <?= e($c['full_name']) ?> (<?= e($c['login']) ?>) · <?= e(date('d/m/Y H:i', strtotime($c['created_at']))) ?></div>
            <div style="margin-top:8px"><?= $c['body'] ?></div>
            <form method="post" action="/staff" style="margin-top:8px">
              <input type="hidden" name="action" value="answer">
              <input type="hidden" name="complaint_id" value="<?= (int)$c['id'] ?>">
              <button class="btn btn-ghost" style="padding:5px 10px;font-size:.85rem">Marquer traitée</button>
            </form>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <?php /* ─────────────── ADMIN : utilisateurs + journaux ─────────────── */ if ($user['role'] === 'admin'): ?>
    <div class="card">
      <h2>Comptes</h2>
      <table><thead><tr><th>#</th><th>Nom</th><th>Rôle</th><th>Compte</th><th style="text-align:right">Solde</th><th></th></tr></thead><tbody>
      <?php foreach ($users as $x): ?>
        <tr>
          <td class="muted"><?= (int)$x['id'] ?></td>
          <td><?= e($x['full_name']) ?><br><span class="muted" style="font-size:.8rem"><?= e($x['login']) ?></span></td>
          <td>
            <form method="post" action="/staff" style="display:inline">
              <input type="hidden" name="action" value="setrole">
              <input type="hidden" name="user_id" value="<?= (int)$x['id'] ?>">
              <select name="role" onchange="this.form.submit()">
                <?php foreach (['client','agent','merchant','support','admin'] as $r): ?>
                  <option value="<?= $r ?>"<?= $x['role']===$r?' selected':'' ?>><?= $r ?></option>
                <?php endforeach; ?>
              </select>
            </form>
          </td>
          <td class="muted"><?= e($x['account_no'] ?? '—') ?></td>
          <td style="text-align:right"><?= e(fcfa((int)($x['balance'] ?? 0))) ?></td>
          <td>
            <form method="post" action="/staff" style="display:inline">
              <input type="hidden" name="action" value="freeze">
              <input type="hidden" name="user_id" value="<?= (int)$x['id'] ?>">
              <button class="btn btn-ghost" style="padding:4px 8px;font-size:.8rem">Geler/Dégeler</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody></table>
    </div>
    <div class="card">
      <h2>Journal de sécurité</h2>
      <table><thead><tr><th>Date</th><th>Acteur</th><th>Action</th><th>Cible</th><th>Résultat</th></tr></thead><tbody>
      <?php foreach ($events as $ev): ?>
        <tr><td class="muted"><?= e(date('d/m H:i', strtotime($ev['created_at']))) ?></td>
            <td><?= e($ev['actor'] ?? '—') ?></td><td><?= e($ev['action']) ?></td>
            <td><?= e($ev['target'] ?? '—') ?></td><td><?= e($ev['result'] ?? '—') ?></td></tr>
      <?php endforeach; ?>
      </tbody></table>
    </div>
  <?php endif; ?>
</div>
