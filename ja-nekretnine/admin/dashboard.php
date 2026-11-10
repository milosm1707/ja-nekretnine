<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$pdo = getDB();
$nekretnine = $pdo->query('SELECT * FROM nekretnine ORDER BY created_at DESC')->fetchAll();

$page_title = 'Nekretnine';
include __DIR__ . '/includes/header.php';
?>

<div class="admin-toolbar">
  <h1>Sve nekretnine</h1>
  <a href="nekretnina-forma.php" class="btn-primary">+ Dodaj nekretninu</a>
</div>

<?php if (empty($nekretnine)): ?>
  <p class="empty-state">Još uvek nema nekretnina. Klikni "Dodaj nekretninu" da dodaš prvu.</p>
<?php else: ?>

  <div class="admin-search-bar">
    <input type="text" id="pretragaNekretnina" class="admin-search-input" placeholder="Pretraži po nazivu...">
    <div class="admin-filter-btns">
      <button class="admin-filter-btn active" data-filter="sve">Sve</button>
      <button class="admin-filter-btn" data-filter="Prodaja">Prodaja</button>
      <button class="admin-filter-btn" data-filter="Iznajmljivanje">Iznajmljivanje</button>
      <button class="admin-filter-btn" data-filter="Stan">Stanovi</button>
      <button class="admin-filter-btn" data-filter="Kuca">Kuće</button>
      <button class="admin-filter-btn" data-filter="Poslovni prostor">Poslovni</button>
    </div>
  </div>

  <div class="admin-table-wrap">
    <table class="admin-table" id="tabelaNekretnina">
      <thead>
        <tr>
          <th>Slika</th>
          <th>Naziv</th>
          <th>Tip</th>
          <th>Status</th>
          <th>Kategorija</th>
          <th>Cena</th>
          <th>Aktivna</th>
          <th>Akcije</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($nekretnine as $n): ?>
          <tr data-naziv="<?= clean(mb_strtolower($n['naziv'])) ?>" data-tip="<?= clean($n['tip']) ?>" data-kat="<?= clean($n['kategorija']) ?>">
            <td class="thumb-cell">
              <?php if ($n['naslovna_slika']): ?>
                <img src="../uploads/nekretnine/<?= clean($n['naslovna_slika']) ?>" alt="">
              <?php else: ?>
                <div class="thumb-placeholder">—</div>
              <?php endif; ?>
            </td>
            <td><?= clean($n['naziv']) ?></td>
            <td><span class="tag tag--<?= $n['tip'] === 'Iznajmljivanje' ? 'rent' : 'sale' ?>"><?= clean($n['tip']) ?></span></td>
            <td>
              <?php if ($n['tip'] === 'Prodaja'): ?>
                <select class="status-select status-select--<?= strtolower($n['status_prodaje']) ?>" data-id="<?= (int)$n['id'] ?>">
                  <option value="Slobodno" <?= $n['status_prodaje'] === 'Slobodno' ? 'selected' : '' ?>>Slobodno</option>
                  <option value="Rezervisano" <?= $n['status_prodaje'] === 'Rezervisano' ? 'selected' : '' ?>>Rezervisano</option>
                  <option value="Prodato" <?= $n['status_prodaje'] === 'Prodato' ? 'selected' : '' ?>>Prodato</option>
                </select>
              <?php else: ?>
                <span class="muted">—</span>
              <?php endif; ?>
            </td>
            <td><?= clean($n['kategorija']) ?></td>
            <td><?= clean($n['cijena']) ?></td>
            <td><?= $n['aktivna'] ? 'Da' : '<span class="muted">Ne</span>' ?></td>
            <td class="actions-cell">
              <a href="nekretnina-forma.php?id=<?= (int)$n['id'] ?>">Izmeni</a>
              <?php if ($n['tip'] === 'Iznajmljivanje'): ?>
                · <a href="kalendar.php?id=<?= (int)$n['id'] ?>">Kalendar</a>
              <?php endif; ?>
              · <form method="post" action="nekretnina-obrisi.php" style="display:inline" onsubmit="return confirm('Sigurno obrisati ovu nekretninu i sve njene slike?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
                  <button type="submit" class="link-danger">Obriši</button>
                </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <p id="nemaRezultata" class="empty-state" style="display:none">Nema nekretnina koje odgovaraju pretrazi.</p>
  </div>

  <script>
    (function () {
      const searchInput = document.getElementById('pretragaNekretnina');
      const filterBtns = document.querySelectorAll('.admin-filter-btn');
      const rows = document.querySelectorAll('#tabelaNekretnina tbody tr');
      const nemaRezultata = document.getElementById('nemaRezultata');
      let aktivniFilter = 'sve';

      function primeni() {
        const upit = searchInput.value.trim().toLowerCase();
        let vidljivo = 0;

        rows.forEach(row => {
          const naziv = row.dataset.naziv || '';
          const tip = row.dataset.tip || '';
          const kat = row.dataset.kat || '';
          const odgovaraFilteru = aktivniFilter === 'sve' || tip === aktivniFilter || kat === aktivniFilter;
          const odgovaraPretrazi = !upit || naziv.includes(upit);
          const prikazi = odgovaraFilteru && odgovaraPretrazi;
          row.style.display = prikazi ? '' : 'none';
          if (prikazi) vidljivo++;
        });

        nemaRezultata.style.display = vidljivo === 0 ? '' : 'none';
      }

      searchInput.addEventListener('input', primeni);
      filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
          filterBtns.forEach(b => b.classList.remove('active'));
          btn.classList.add('active');
          aktivniFilter = btn.dataset.filter;
          primeni();
        });
      });
    })();

    (function () {
      const CSRF_TOKEN = <?= json_encode(csrf_token()) ?>;
      document.querySelectorAll('.status-select').forEach(select => {
        select.addEventListener('change', () => {
          const noviStatus = select.value;
          const id = select.dataset.id;
          select.disabled = true;

          fetch('status-prodaje-ajax.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ id, status_prodaje: noviStatus, csrf_token: CSRF_TOKEN }),
          })
            .then(r => r.json())
            .then(data => {
              select.disabled = false;
              if (data.success) {
                select.className = 'status-select status-select--' + noviStatus.toLowerCase();
              } else {
                alert(data.message || 'Greška pri čuvanju statusa.');
              }
            })
            .catch(() => {
              select.disabled = false;
              alert('Greška u komunikaciji sa serverom.');
            });
        });
      });
    })();
  </script>

<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
