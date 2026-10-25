<div class="container">
  <h1>Mes bénéficiaires</h1>

  <div class="grid-2">
    <div class="card">
      <h2>Ajouter un bénéficiaire</h2>
      <?php foreach ($errors as $err): ?>
        <div class="alert alert-error"><?= e($err) ?></div>
      <?php endforeach; ?>
      <form method="post" action="/beneficiaries">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add">
        <div class="field">
          <label for="dst_account">Compte ICS Bank</label>
          <input type="text" id="dst_account" name="dst_account" placeholder="ICS0001234" required>
        </div>
        <div class="field">
          <label for="nickname">Nom / surnom</label>
          <input type="text" id="nickname" name="nickname" maxlength="80">
        </div>
        <button type="submit" class="btn btn-block">Ajouter</button>
      </form>
    </div>

    <div class="card">
      <h2>Enregistrés</h2>
      <?php if (!$beneficiaries): ?>
        <p class="muted">Aucun bénéficiaire pour le moment.</p>
      <?php else: ?>
        <table>
          <thead><tr><th>Nom</th><th>Compte</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($beneficiaries as $b): ?>
            <tr>
              <td><?= e($b['nickname']) ?></td>
              <td class="muted"><?= e($b['dst_account']) ?></td>
              <td style="text-align:right">
                <form method="post" action="/beneficiaries" style="display:inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                  <button class="btn btn-ghost" style="padding:5px 10px;font-size:.85rem">Supprimer</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
</div>
