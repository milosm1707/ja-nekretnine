<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$pdo = getDB();
$id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
$editing = $id > 0;

$zgrada = [
    'naziv' => '', 'opis' => '', 'lokacija' => '', 'adresa' => '', 'lat' => '', 'lng' => '',
    'godina_izgradnje' => '', 'broj_spratova' => '', 'video_url' => '',
    'dokument' => null, 'istaknuta' => 0, 'aktivna' => 1,
];
$slike = [];
$errors = [];

if ($editing) {
    $stmt = $pdo->prepare('SELECT * FROM zgrade WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        flash_set('error', 'Zgrada nije pronađena.');
        redirect('zgrade-dashboard.php');
    }
    $zgrada = $found;

    $stmt = $pdo->prepare('SELECT * FROM slike_zgrada WHERE zgrada_id = ? ORDER BY redosled ASC, id ASC');
    $stmt->execute([$id]);
    $slike = $stmt->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['poveži_id']) && !isset($_POST['ukloni_id'])) {
    if (!csrf_verify()) {
        $errors[] = 'Sesija je istekla, pokušaj ponovo.';
    } else {
        $zgrada['naziv'] = trim($_POST['naziv'] ?? '');
        $zgrada['opis'] = trim($_POST['opis'] ?? '');
        $zgrada['lokacija'] = trim($_POST['lokacija'] ?? '');
        $zgrada['adresa'] = trim($_POST['adresa'] ?? '');
        $zgrada['lat'] = trim($_POST['lat'] ?? '') !== '' ? (float)$_POST['lat'] : null;
        $zgrada['lng'] = trim($_POST['lng'] ?? '') !== '' ? (float)$_POST['lng'] : null;
        $zgrada['godina_izgradnje'] = $_POST['godina_izgradnje'] !== '' ? (int)$_POST['godina_izgradnje'] : null;
        $zgrada['broj_spratova'] = $_POST['broj_spratova'] !== '' ? (int)$_POST['broj_spratova'] : null;
        $zgrada['video_url'] = trim($_POST['video_url'] ?? '');
        $zgrada['istaknuta'] = isset($_POST['istaknuta']) ? 1 : 0;
        $zgrada['aktivna'] = isset($_POST['aktivna']) ? 1 : 0;

        if ($zgrada['naziv'] === '') $errors[] = 'Naziv je obavezan.';

        $obrisi_ids = $_POST['obrisi_slika'] ?? [];

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                if ($editing) {
                    $stmt = $pdo->prepare('UPDATE zgrade SET naziv=?, opis=?, lokacija=?, adresa=?, lat=?, lng=?, godina_izgradnje=?, broj_spratova=?, video_url=?, istaknuta=?, aktivna=? WHERE id=?');
                    $stmt->execute([
                        $zgrada['naziv'], $zgrada['opis'], $zgrada['lokacija'], $zgrada['adresa'], $zgrada['lat'], $zgrada['lng'],
                        $zgrada['godina_izgradnje'], $zgrada['broj_spratova'], $zgrada['video_url'],
                        $zgrada['istaknuta'], $zgrada['aktivna'], $id,
                    ]);
                } else {
                    $stmt = $pdo->prepare('INSERT INTO zgrade (naziv, opis, lokacija, adresa, lat, lng, godina_izgradnje, broj_spratova, video_url, istaknuta, aktivna) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
                    $stmt->execute([
                        $zgrada['naziv'], $zgrada['opis'], $zgrada['lokacija'], $zgrada['adresa'], $zgrada['lat'], $zgrada['lng'],
                        $zgrada['godina_izgradnje'], $zgrada['broj_spratova'], $zgrada['video_url'],
                        $zgrada['istaknuta'], $zgrada['aktivna'],
                    ]);
                    $id = (int)$pdo->lastInsertId();
                }

                // brisanje označenih slika
                if (!empty($obrisi_ids)) {
                    $obrisi_ids = array_map('intval', $obrisi_ids);
                    $placeholders = implode(',', array_fill(0, count($obrisi_ids), '?'));
                    $stmt = $pdo->prepare("SELECT * FROM slike_zgrada WHERE id IN ($placeholders) AND zgrada_id = ?");
                    $stmt->execute([...$obrisi_ids, $id]);
                    $za_brisanje = $stmt->fetchAll();

                    $stmt = $pdo->prepare("DELETE FROM slike_zgrada WHERE id IN ($placeholders) AND zgrada_id = ?");
                    $stmt->execute([...$obrisi_ids, $id]);

                    foreach ($za_brisanje as $s) {
                        delete_image_file($s['fajl'], UPLOAD_ZGRADE_DIR);
                    }
                }

                // upload novih slika
                if (!empty($_FILES['slike']['name'][0])) {
                    $stmt = $pdo->prepare('SELECT COALESCE(MAX(redosled), -1) FROM slike_zgrada WHERE zgrada_id = ?');
                    $stmt->execute([$id]);
                    $redosled = (int)$stmt->fetchColumn() + 1;

                    $count = count($_FILES['slike']['name']);
                    for ($i = 0; $i < $count; $i++) {
                        if ($_FILES['slike']['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
                        $file = [
                            'name' => $_FILES['slike']['name'][$i],
                            'type' => $_FILES['slike']['type'][$i],
                            'tmp_name' => $_FILES['slike']['tmp_name'][$i],
                            'error' => $_FILES['slike']['error'][$i],
                            'size' => $_FILES['slike']['size'][$i],
                        ];
                        $filename = save_uploaded_image($file, UPLOAD_ZGRADE_DIR);
                        $stmt = $pdo->prepare("INSERT INTO slike_zgrada (zgrada_id, fajl, tip, redosled) VALUES (?, ?, 'slika', ?)");
                        $stmt->execute([$id, $filename, $redosled]);
                        $redosled++;
                    }
                }

                // upload video fajla (MP4) — ide u istu galeriju kao slike
                if (!empty($_FILES['video_fajl']['name']) && $_FILES['video_fajl']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $stmt = $pdo->prepare('SELECT COALESCE(MAX(redosled), -1) FROM slike_zgrada WHERE zgrada_id = ?');
                    $stmt->execute([$id]);
                    $redosled = (int)$stmt->fetchColumn() + 1;

                    $videoFilename = save_uploaded_video($_FILES['video_fajl'], UPLOAD_ZGRADE_DIR);
                    $stmt = $pdo->prepare("INSERT INTO slike_zgrada (zgrada_id, fajl, tip, redosled) VALUES (?, ?, 'video', ?)");
                    $stmt->execute([$id, $videoFilename, $redosled]);
                }

                // ukloni postojeći dokument ako je zatraženo
                if (!empty($_POST['ukloni_dokument']) && !empty($zgrada['dokument'])) {
                    delete_document_file($zgrada['dokument']);
                    $stmt = $pdo->prepare('UPDATE zgrade SET dokument = NULL WHERE id = ?');
                    $stmt->execute([$id]);
                    $zgrada['dokument'] = null;
                }

                // upload novog dokumenta (zamenjuje stari ako postoji)
                if (!empty($_FILES['dokument']['name']) && $_FILES['dokument']['error'] !== UPLOAD_ERR_NO_FILE) {
                    if (!empty($zgrada['dokument'])) {
                        delete_document_file($zgrada['dokument']);
                    }
                    $docFilename = save_uploaded_document($_FILES['dokument']);
                    $stmt = $pdo->prepare('UPDATE zgrade SET dokument = ? WHERE id = ?');
                    $stmt->execute([$docFilename, $id]);
                }

                // ažuriraj naslovnu sliku na prvu SLIKU iz galerije (video se ne koristi kao naslovna)
                $stmt = $pdo->prepare("SELECT fajl FROM slike_zgrada WHERE zgrada_id = ? AND tip = 'slika' ORDER BY redosled ASC, id ASC LIMIT 1");
                $stmt->execute([$id]);
                $prva = $stmt->fetchColumn();
                $stmt = $pdo->prepare('UPDATE zgrade SET naslovna_slika = ? WHERE id = ?');
                $stmt->execute([$prva ?: null, $id]);

                $pdo->commit();

                flash_set('success', $editing ? 'Zgrada je ažurirana.' : 'Zgrada je dodata. Sada možeš da povežeš stanove sa njom.');
                redirect('zgrada-forma.php?id=' . $id);
            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = 'Greška: ' . $e->getMessage();
            }
        }

        if ($editing) {
            $stmt = $pdo->prepare('SELECT * FROM slike_zgrada WHERE zgrada_id = ? ORDER BY redosled ASC, id ASC');
            $stmt->execute([$id]);
            $slike = $stmt->fetchAll();
        }
    }
}

// ── povezivanje / uklanjanje stanova (samo kad zgrada već postoji) ──
if ($editing && $_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['poveži_id']) || isset($_POST['ukloni_id']))) {
    if (csrf_verify()) {
        if (isset($_POST['poveži_id'])) {
            $stmt = $pdo->prepare('UPDATE nekretnine SET zgrada_id = ? WHERE id = ?');
            $stmt->execute([$id, (int)$_POST['poveži_id']]);
        } elseif (isset($_POST['ukloni_id'])) {
            $stmt = $pdo->prepare('UPDATE nekretnine SET zgrada_id = NULL WHERE id = ? AND zgrada_id = ?');
            $stmt->execute([(int)$_POST['ukloni_id'], $id]);
        }
    }
    redirect('zgrada-forma.php?id=' . $id);
}

$povezaniStanovi = [];
$nepovezaniStanovi = [];
if ($editing) {
    $stmt = $pdo->prepare('SELECT id, naziv, tip, cijena FROM nekretnine WHERE zgrada_id = ? ORDER BY naziv ASC');
    $stmt->execute([$id]);
    $povezaniStanovi = $stmt->fetchAll();

    $nepovezaniStanovi = $pdo->query('SELECT id, naziv, tip, cijena FROM nekretnine WHERE zgrada_id IS NULL ORDER BY naziv ASC')->fetchAll();
}

$page_title = $editing ? 'Izmena zgrade' : 'Nova zgrada';
include __DIR__ . '/includes/header.php';
?>

<h1><?= $editing ? 'Izmeni zgradu' : 'Dodaj novu zgradu' ?></h1>

<?php foreach ($errors as $err): ?>
  <div class="alert alert--error"><?= clean($err) ?></div>
<?php endforeach; ?>

<form method="post" enctype="multipart/form-data" class="admin-form">
  <?= csrf_field() ?>
  <?php if ($editing): ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>

  <div class="form-row">
    <div class="field">
      <label>Naziv zgrade *</label>
      <input type="text" name="naziv" required value="<?= clean($zgrada['naziv']) ?>">
    </div>
    <div class="field">
      <label>Lokacija (grad)</label>
      <input type="text" name="lokacija" value="<?= clean($zgrada['lokacija']) ?>">
    </div>
  </div>

  <div class="form-row">
    <div class="field">
      <label>Adresa</label>
      <input type="text" name="adresa" value="<?= clean($zgrada['adresa']) ?>">
    </div>
    <div class="field">
      <label>Geografska širina (lat)</label>
      <input type="text" name="lat" placeholder="npr. 44.7722" value="<?= clean((string)($zgrada['lat'] ?? '')) ?>">
    </div>
    <div class="field">
      <label>Geografska dužina (lng)</label>
      <input type="text" name="lng" placeholder="npr. 17.1910" value="<?= clean((string)($zgrada['lng'] ?? '')) ?>">
    </div>
  </div>
  <p class="field-hint" style="margin-top:-14px;margin-bottom:16px">
    Kako da nađeš koordinate: otvori <a href="https://www.google.com/maps" target="_blank">Google Maps</a>, desni klik na tačnu lokaciju zgrade, klikni na prvi broj (npr. "44.7722, 17.1910") da ga kopiraš, pa nalepi prvi broj u "lat" a drugi u "lng". Ako ostaviš prazno, mapa se neće prikazati u detaljima.
  </p>

  <div class="form-row">
    <div class="field">
      <label>Godina izgradnje</label>
      <input type="number" name="godina_izgradnje" value="<?= clean((string)($zgrada['godina_izgradnje'] ?? '')) ?>">
    </div>
    <div class="field">
      <label>Broj spratova</label>
      <input type="number" name="broj_spratova" value="<?= clean((string)($zgrada['broj_spratova'] ?? '')) ?>">
    </div>
  </div>

  <div class="field">
    <label>Opis</label>
    <textarea name="opis" rows="5"><?= clean($zgrada['opis']) ?></textarea>
  </div>

  <div class="field">
    <label>Video link (YouTube, Vimeo ili Instagram) — prikazuje se kao posebna sekcija</label>
    <input type="text" name="video_url" placeholder="https://www.youtube.com/watch?v=... ili https://www.instagram.com/reel/..." value="<?= clean($zgrada['video_url']) ?>">
  </div>

  <div class="form-row">
    <label class="checkbox-label">
      <input type="checkbox" name="istaknuta" <?= $zgrada['istaknuta'] ? 'checked' : '' ?>> Istaknuta zgrada (prikaz na početnoj)
    </label>
    <label class="checkbox-label">
      <input type="checkbox" name="aktivna" <?= $zgrada['aktivna'] ? 'checked' : '' ?>> Aktivna (prikazana na sajtu)
    </label>
  </div>

  <?php if (!empty($slike)): ?>
    <div class="field">
      <label>Postojeća galerija (obeleži za brisanje)</label>
      <div class="gallery-grid">
        <?php foreach ($slike as $s): ?>
          <div class="gallery-item">
            <?php if ($s['tip'] === 'video'): ?>
              <div class="gallery-item__video-placeholder" style="width:100%;height:90px;border-radius:4px;margin-bottom:6px;background:linear-gradient(135deg,#1c1c1c,#0d0d0d);border:1px solid #282828;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;">
                <span class="gallery-item__video-play" style="width:28px;height:28px;border-radius:50%;background:#7ab33a;color:#fff;display:flex;align-items:center;justify-content:center;font-size:11px;padding-left:2px;">▶</span>
                <span class="gallery-item__video-label" style="font-size:10px;color:#b8b8b0;text-transform:uppercase;letter-spacing:0.08em;">Video</span>
              </div>
            <?php else: ?>
              <img src="../uploads/zgrade/<?= clean($s['fajl']) ?>" alt="" style="width:100%;height:90px;object-fit:cover;border-radius:4px;margin-bottom:6px;display:block;">
            <?php endif; ?>
            <label class="checkbox-label small">
              <input type="checkbox" name="obrisi_slika[]" value="<?= (int)$s['id'] ?>"> Obriši
            </label>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <div class="field">
    <label>Dodaj nove slike (JPG, PNG ili WEBP, max 5MB po slici)</label>
    <input type="file" name="slike[]" multiple accept="image/jpeg,image/png,image/webp">
    <p class="field-hint">Savet: da izabereš više slika odjednom, drži <strong>Ctrl</strong> (Windows) ili <strong>Cmd</strong> (Mac) i klikni na svaku sliku.</p>
  </div>

  <div class="field">
    <label>Dodaj video u galeriju (MP4, max <?= (int)(MAX_VIDEO_SIZE / 1024 / 1024) ?>MB)</label>
    <input type="file" name="video_fajl" accept="video/mp4">
    <p class="field-hint">Video će se prikazati među slikama u galeriji — posetilac ga listaju zajedno sa fotografijama.</p>
  </div>

  <div class="field">
    <label>Dokument (PDF — brošura, tlocrt i sl.)</label>
    <?php if (!empty($zgrada['dokument'])): ?>
      <p class="field-hint">
        Trenutni dokument: <a href="../uploads/dokumenti/<?= clean($zgrada['dokument']) ?>" target="_blank">preuzmi / pogledaj</a>
        &nbsp;·&nbsp;
        <label class="checkbox-label small"><input type="checkbox" name="ukloni_dokument" value="1"> Ukloni dokument</label>
      </p>
    <?php endif; ?>
    <input type="file" name="dokument" accept="application/pdf">
  </div>

  <div class="form-actions">
    <button type="submit" class="btn-primary"><?= $editing ? 'Sačuvaj izmene' : 'Dodaj zgradu' ?></button>
    <a href="zgrade-dashboard.php" class="btn-secondary">Otkaži</a>
  </div>
</form>

<?php if ($editing): ?>
  <div class="admin-form" style="margin-top:32px">
    <h2 style="margin-bottom:16px">Stanovi u ovoj zgradi</h2>

    <?php if (empty($povezaniStanovi)): ?>
      <p class="empty-state" style="padding:20px 0">Još uvek nema povezanih stanova.</p>
    <?php else: ?>
      <table class="admin-table" style="margin-bottom:24px">
        <thead><tr><th>Naziv</th><th>Tip</th><th>Cena</th><th>Akcije</th></tr></thead>
        <tbody>
          <?php foreach ($povezaniStanovi as $s): ?>
            <tr>
              <td><?= clean($s['naziv']) ?></td>
              <td><span class="tag tag--<?= $s['tip'] === 'Iznajmljivanje' ? 'rent' : 'sale' ?>"><?= clean($s['tip']) ?></span></td>
              <td><?= clean($s['cijena']) ?></td>
              <td class="actions-cell">
                <a href="nekretnina-forma.php?id=<?= (int)$s['id'] ?>">Izmeni</a>
                · <form method="post" style="display:inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <input type="hidden" name="ukloni_id" value="<?= (int)$s['id'] ?>">
                    <button type="submit" class="link-danger">Ukloni iz zgrade</button>
                  </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>

    <h3 style="font-size:15px;color:var(--light-mid);margin-bottom:12px">Poveži postojeću nekretninu</h3>
    <?php if (empty($nepovezaniStanovi)): ?>
      <p class="empty-state" style="padding:12px 0">Nema slobodnih nekretnina (sve su već povezane sa nekom zgradom).</p>
    <?php else: ?>
      <input type="text" id="pretragaPovezivanje" class="admin-search-input" placeholder="Pretraži nekretnine za povezivanje..." style="margin-bottom:12px">
      <table class="admin-table" id="tabelaPovezivanje">
        <tbody>
          <?php foreach ($nepovezaniStanovi as $s): ?>
            <tr data-naziv="<?= clean(mb_strtolower($s['naziv'])) ?>">
              <td><?= clean($s['naziv']) ?></td>
              <td><span class="tag tag--<?= $s['tip'] === 'Iznajmljivanje' ? 'rent' : 'sale' ?>"><?= clean($s['tip']) ?></span></td>
              <td><?= clean($s['cijena']) ?></td>
              <td class="actions-cell">
                <form method="post" style="display:inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= $id ?>">
                  <input type="hidden" name="poveži_id" value="<?= (int)$s['id'] ?>">
                  <button type="submit" class="btn-secondary btn-small">+ Poveži</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>

    <p style="margin-top:20px">
      <a href="nekretnina-forma.php?zgrada_id=<?= $id ?>" class="btn-secondary">+ Dodaj novu nekretninu direktno u ovu zgradu</a>
    </p>
  </div>

  <script>
    (function () {
      const searchInput = document.getElementById('pretragaPovezivanje');
      if (!searchInput) return;
      const rows = document.querySelectorAll('#tabelaPovezivanje tbody tr');
      searchInput.addEventListener('input', () => {
        const upit = searchInput.value.trim().toLowerCase();
        rows.forEach(row => {
          row.style.display = (!upit || (row.dataset.naziv || '').includes(upit)) ? '' : 'none';
        });
      });
    })();
  </script>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
