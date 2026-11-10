<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$pdo = getDB();
$zgrade = $pdo->query('
    SELECT z.*, (SELECT COUNT(*) FROM nekretnine n WHERE n.zgrada_id = z.id) AS broj_stanova
    FROM zgrade z
    ORDER BY z.created_at DESC
')->fetchAll();

$page_title = 'Zgrade';
include __DIR__ . '/includes/header.php';
?>

<div class="admin-toolbar">
  <h1>Sve zgrade</h1>
  <a href="zgrada-forma.php" class="btn-primary">+ Dodaj zgradu</a>
</div>

<?php if (empty($zgrade)): ?>
  <p class="empty-state">Još uvek nema zgrada. Klikni "Dodaj zgradu" da dodaš prvu.</p>
<?php else: ?>

  <div class="admin-search-bar">
    <input type="text" id="pretragaZgrada" class="admin-search-input" placeholder="Pretraži po nazivu...">
  </div>

  <div class="admin-table-wrap">
    <table class="admin-table" id="tabelaZgrada">
      <thead>
        <tr>
          <th>Slika</th>
          <th>Naziv</th>
          <th>Lokacija</th>
          <th>Stanova</th>
          <th>Aktivna</th>
          <th>Akcije</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($zgrade as $z): ?>
          <tr data-naziv="<?= clean(mb_strtolower($z['naziv'])) ?>">
            <td class="thumb-cell">
              <?php if ($z['naslovna_slika']): ?>
                <img src="../uploads/zgrade/<?= clean($z['naslovna_slika']) ?>" alt="">
              <?php else: ?>
                <div class="thumb-placeholder">—</div>
              <?php endif; ?>
            </td>
            <td><?= clean($z['naziv']) ?></td>
            <td><?= clean($z['lokacija']) ?></td>
            <td><?= (int)$z['broj_stanova'] ?></td>
            <td><?= $z['aktivna'] ? 'Da' : '<span class="muted">Ne</span>' ?></td>
            <td class="actions-cell">
              <a href="zgrada-forma.php?id=<?= (int)$z['id'] ?>">Izmeni</a>
              · <form method="post" action="zgrada-obrisi.php" style="display:inline" onsubmit="return confirm('Sigurno obrisati ovu zgradu? Stanovi koji joj pripadaju NEĆE biti obrisani, samo će izgubiti vezu sa zgradom.');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int)$z['id'] ?>">
                  <button type="submit" class="link-danger">Obriši</button>
                </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <p id="nemaRezultataZgrade" class="empty-state" style="display:none">Nema zgrada koje odgovaraju pretrazi.</p>
  </div>

  <script>
    (function () {
      const searchInput = document.getElementById('pretragaZgrada');
      const rows = document.querySelectorAll('#tabelaZgrada tbody tr');
      const nemaRezultata = document.getElementById('nemaRezultataZgrade');

      searchInput.addEventListener('input', () => {
        const upit = searchInput.value.trim().toLowerCase();
        let vidljivo = 0;
        rows.forEach(row => {
          const prikazi = !upit || (row.dataset.naziv || '').includes(upit);
          row.style.display = prikazi ? '' : 'none';
          if (prikazi) vidljivo++;
        });
        nemaRezultata.style.display = vidljivo === 0 ? '' : 'none';
      });
    })();
  </script>

<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
