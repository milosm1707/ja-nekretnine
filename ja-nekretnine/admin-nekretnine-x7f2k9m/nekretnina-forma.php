<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$pdo = getDB();
$id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
$editing = $id > 0;

$nekretnina = [
    'naziv' => '', 'opis' => '', 'tip' => 'Prodaja', 'kategorija' => 'Stan', 'status_prodaje' => 'Slobodno',
    'cijena' => '', 'lokacija' => '', 'lat' => '', 'lng' => '', 'video_url' => '', 'dokument' => null, 'kvadratura' => '', 'sobe' => 0,
    'istaknuta' => 0, 'aktivna' => 1, 'zgrada_id' => null,
];
$slike = [];
$errors = [];

// ako stižemo sa "+ Dodaj novu nekretninu u ovu zgradu", predpopuni zgrada_id
if (!$editing && !empty($_GET['zgrada_id'])) {
    $nekretnina['zgrada_id'] = (int)$_GET['zgrada_id'];
}

$sveZgrade = $pdo->query('SELECT id, naziv FROM zgrade ORDER BY naziv ASC')->fetchAll();

if ($editing) {
    $stmt = $pdo->prepare('SELECT * FROM nekretnine WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        flash_set('error', 'Nekretnina nije pronađena.');
        redirect('dashboard.php');
    }
    $nekretnina = $found;

    $stmt = $pdo->prepare('SELECT * FROM slike_nekretnina WHERE nekretnina_id = ? ORDER BY redosled ASC, id ASC');
    $stmt->execute([$id]);
    $slike = $stmt->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Sesija je istekla, pokušaj ponovo.';
    } else {
        $nekretnina['naziv'] = trim($_POST['naziv'] ?? '');
        $nekretnina['opis'] = trim($_POST['opis'] ?? '');
        $nekretnina['tip'] = in_array($_POST['tip'] ?? '', ['Prodaja', 'Iznajmljivanje'], true) ? $_POST['tip'] : 'Prodaja';
        $nekretnina['status_prodaje'] = in_array($_POST['status_prodaje'] ?? '', ['Slobodno', 'Rezervisano', 'Prodato'], true) ? $_POST['status_prodaje'] : 'Slobodno';
        $nekretnina['kategorija'] = in_array($_POST['kategorija'] ?? '', ['Stan', 'Kuca', 'Poslovni prostor'], true) ? $_POST['kategorija'] : 'Stan';
        $nekretnina['cijena'] = trim($_POST['cijena'] ?? '');
        $nekretnina['lokacija'] = trim($_POST['lokacija'] ?? '');
        $nekretnina['lat'] = trim($_POST['lat'] ?? '') !== '' ? (float)$_POST['lat'] : null;
        $nekretnina['lng'] = trim($_POST['lng'] ?? '') !== '' ? (float)$_POST['lng'] : null;
        $nekretnina['video_url'] = trim($_POST['video_url'] ?? '');
        $nekretnina['kvadratura'] = (float)($_POST['kvadratura'] ?? 0);
        $nekretnina['sobe'] = (int)($_POST['sobe'] ?? 0);
        $nekretnina['istaknuta'] = isset($_POST['istaknuta']) ? 1 : 0;
        $nekretnina['aktivna'] = isset($_POST['aktivna']) ? 1 : 0;
        $nekretnina['zgrada_id'] = !empty($_POST['zgrada_id']) ? (int)$_POST['zgrada_id'] : null;

        if ($nekretnina['naziv'] === '') $errors[] = 'Naziv je obavezan.';
        if ($nekretnina['cijena'] === '') $errors[] = 'Cena je obavezna.';

        // obradi brisanje postojećih slika
        $obrisi_ids = $_POST['obrisi_slika'] ?? [];
        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                if ($editing) {
                    $stmt = $pdo->prepare('UPDATE nekretnine SET naziv=?, opis=?, tip=?, status_prodaje=?, kategorija=?, zgrada_id=?, cijena=?, lokacija=?, lat=?, lng=?, video_url=?, kvadratura=?, sobe=?, istaknuta=?, aktivna=? WHERE id=?');
                    $stmt->execute([
                        $nekretnina['naziv'], $nekretnina['opis'], $nekretnina['tip'], $nekretnina['status_prodaje'], $nekretnina['kategorija'],
                        $nekretnina['zgrada_id'], $nekretnina['cijena'], $nekretnina['lokacija'], $nekretnina['lat'], $nekretnina['lng'], $nekretnina['video_url'], $nekretnina['kvadratura'], $nekretnina['sobe'],
                        $nekretnina['istaknuta'], $nekretnina['aktivna'], $id,
                    ]);
                } else {
                    $stmt = $pdo->prepare('INSERT INTO nekretnine (naziv, opis, tip, status_prodaje, kategorija, zgrada_id, cijena, lokacija, lat, lng, video_url, kvadratura, sobe, istaknuta, aktivna) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
                    $stmt->execute([
                        $nekretnina['naziv'], $nekretnina['opis'], $nekretnina['tip'], $nekretnina['status_prodaje'], $nekretnina['kategorija'],
                        $nekretnina['zgrada_id'], $nekretnina['cijena'], $nekretnina['lokacija'], $nekretnina['lat'], $nekretnina['lng'], $nekretnina['video_url'], $nekretnina['kvadratura'], $nekretnina['sobe'],
                        $nekretnina['istaknuta'], $nekretnina['aktivna'],
                    ]);
                    $id = (int)$pdo->lastInsertId();
                }

                // brisanje označenih slika
                if (!empty($obrisi_ids)) {
                    $obrisi_ids = array_map('intval', $obrisi_ids);
                    $placeholders = implode(',', array_fill(0, count($obrisi_ids), '?'));
                    $stmt = $pdo->prepare("SELECT * FROM slike_nekretnina WHERE id IN ($placeholders) AND nekretnina_id = ?");
                    $stmt->execute([...$obrisi_ids, $id]);
                    $za_brisanje = $stmt->fetchAll();

                    $stmt = $pdo->prepare("DELETE FROM slike_nekretnina WHERE id IN ($placeholders) AND nekretnina_id = ?");
                    $stmt->execute([...$obrisi_ids, $id]);

                    foreach ($za_brisanje as $s) {
                        delete_image_file($s['fajl']);
                    }
                }

                // upload novih slika
                if (!empty($_FILES['slike']['name'][0])) {
                    $stmt = $pdo->prepare('SELECT COALESCE(MAX(redosled), -1) FROM slike_nekretnina WHERE nekretnina_id = ?');
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
                        $filename = save_uploaded_image($file);
                        $stmt = $pdo->prepare('INSERT INTO slike_nekretnina (nekretnina_id, fajl, tip, redosled) VALUES (?, ?, \'slika\', ?)');
                        $stmt->execute([$id, $filename, $redosled]);
                        $redosled++;
                    }
                }

                // upload video fajla (MP4) — ide u istu galeriju kao slike
                if (!empty($_FILES['video_fajl']['name']) && $_FILES['video_fajl']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $stmt = $pdo->prepare('SELECT COALESCE(MAX(redosled), -1) FROM slike_nekretnina WHERE nekretnina_id = ?');
                    $stmt->execute([$id]);
                    $redosled = (int)$stmt->fetchColumn() + 1;

                    $videoFilename = save_uploaded_video($_FILES['video_fajl'], UPLOAD_DIR);
                    $stmt = $pdo->prepare('INSERT INTO slike_nekretnina (nekretnina_id, fajl, tip, redosled) VALUES (?, ?, \'video\', ?)');
                    $stmt->execute([$id, $videoFilename, $redosled]);
                }

                // ukloni postojeći dokument ako je zatraženo
                if (!empty($_POST['ukloni_dokument']) && !empty($nekretnina['dokument'])) {
                    delete_document_file($nekretnina['dokument']);
                    $stmt = $pdo->prepare('UPDATE nekretnine SET dokument = NULL WHERE id = ?');
                    $stmt->execute([$id]);
                    $nekretnina['dokument'] = null;
                }

                // upload novog dokumenta (zamenjuje stari ako postoji)
                if (!empty($_FILES['dokument']['name']) && $_FILES['dokument']['error'] !== UPLOAD_ERR_NO_FILE) {
                    if (!empty($nekretnina['dokument'])) {
                        delete_document_file($nekretnina['dokument']);
                    }
                    $docFilename = save_uploaded_document($_FILES['dokument']);
                    $stmt = $pdo->prepare('UPDATE nekretnine SET dokument = ? WHERE id = ?');
                    $stmt->execute([$docFilename, $id]);
                }

                // ažuriraj naslovnu sliku na prvu SLIKU iz galerije (video se ne koristi kao naslovna)
                $stmt = $pdo->prepare("SELECT fajl FROM slike_nekretnina WHERE nekretnina_id = ? AND tip = 'slika' ORDER BY redosled ASC, id ASC LIMIT 1");
                $stmt->execute([$id]);
                $prva = $stmt->fetchColumn();
                $stmt = $pdo->prepare('UPDATE nekretnine SET naslovna_slika = ? WHERE id = ?');
                $stmt->execute([$prva ?: null, $id]);

                $pdo->commit();

                flash_set('success', $editing ? 'Nekretnina je ažurirana.' : 'Nekretnina je dodata.');
                redirect('dashboard.php');
            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = 'Greška: ' . $e->getMessage();
            }
        }

        // ponovo učitaj slike za prikaz forme ako ima grešaka
        if ($editing) {
            $stmt = $pdo->prepare('SELECT * FROM slike_nekretnina WHERE nekretnina_id = ? ORDER BY redosled ASC, id ASC');
            $stmt->execute([$id]);
            $slike = $stmt->fetchAll();
        }
    }
}

$page_title = $editing ? 'Izmena nekretnine' : 'Nova nekretnina';
include __DIR__ . '/includes/header.php';
?>

<h1><?= $editing ? 'Izmeni nekretninu' : 'Dodaj novu nekretninu' ?></h1>

<?php foreach ($errors as $err): ?>
  <div class="alert alert--error"><?= clean($err) ?></div>
<?php endforeach; ?>

<form method="post" enctype="multipart/form-data" class="admin-form">
  <?= csrf_field() ?>
  <?php if ($editing): ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>

  <div class="form-row">
    <div class="field">
      <label>Naziv nekretnine *</label>
      <input type="text" name="naziv" required value="<?= clean($nekretnina['naziv']) ?>">
    </div>
    <div class="field">
      <label>Cena (npr. "650.000 KM" ili "3.500 KM/mj") *</label>
      <input type="text" name="cijena" required value="<?= clean($nekretnina['cijena']) ?>">
    </div>
  </div>

  <div class="form-row">
    <div class="field">
      <label>Tip oglasa *</label>
      <select name="tip" id="tipOglasaSelect">
        <option value="Prodaja" <?= $nekretnina['tip'] === 'Prodaja' ? 'selected' : '' ?>>Prodaja</option>
        <option value="Iznajmljivanje" <?= $nekretnina['tip'] === 'Iznajmljivanje' ? 'selected' : '' ?>>Iznajmljivanje</option>
      </select>
    </div>
    <div class="field" id="statusProdajeField" style="<?= $nekretnina['tip'] !== 'Prodaja' ? 'display:none' : '' ?>">
      <label>Status prodaje</label>
      <select name="status_prodaje">
        <option value="Slobodno" <?= $nekretnina['status_prodaje'] === 'Slobodno' ? 'selected' : '' ?>>Slobodno</option>
        <option value="Rezervisano" <?= $nekretnina['status_prodaje'] === 'Rezervisano' ? 'selected' : '' ?>>Rezervisano</option>
        <option value="Prodato" <?= $nekretnina['status_prodaje'] === 'Prodato' ? 'selected' : '' ?>>Prodato</option>
      </select>
    </div>
    <div class="field">
      <label>Kategorija</label>
      <select name="kategorija">
        <option value="Stan" <?= $nekretnina['kategorija'] === 'Stan' ? 'selected' : '' ?>>Stan</option>
        <option value="Kuca" <?= $nekretnina['kategorija'] === 'Kuca' ? 'selected' : '' ?>>Kuća</option>
        <option value="Poslovni prostor" <?= $nekretnina['kategorija'] === 'Poslovni prostor' ? 'selected' : '' ?>>Poslovni prostor</option>
      </select>
    </div>
  </div>

  <div class="form-row">
    <div class="field">
      <label>Zgrada (opcionalno)</label>
      <select name="zgrada_id">
        <option value="">— Bez zgrade —</option>
        <?php foreach ($sveZgrade as $z): ?>
          <option value="<?= (int)$z['id'] ?>" <?= (int)($nekretnina['zgrada_id'] ?? 0) === (int)$z['id'] ? 'selected' : '' ?>><?= clean($z['naziv']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <script>
    (function () {
      const tipSelect = document.getElementById('tipOglasaSelect');
      const statusField = document.getElementById('statusProdajeField');
      tipSelect.addEventListener('change', () => {
        statusField.style.display = tipSelect.value === 'Prodaja' ? '' : 'none';
      });
    })();
  </script>

  <div class="form-row">
    <div class="field">
      <label>Lokacija</label>
      <input type="text" name="lokacija" value="<?= clean($nekretnina['lokacija']) ?>">
    </div>
    <div class="field">
      <label>Geografska širina (lat)</label>
      <input type="text" name="lat" placeholder="npr. 44.7722" value="<?= clean((string)($nekretnina['lat'] ?? '')) ?>">
    </div>
    <div class="field">
      <label>Geografska dužina (lng)</label>
      <input type="text" name="lng" placeholder="npr. 17.1910" value="<?= clean((string)($nekretnina['lng'] ?? '')) ?>">
    </div>
  </div>
  <p class="field-hint" style="margin-top:-14px;margin-bottom:16px">
    Kako da nađeš koordinate: otvori <a href="https://www.google.com/maps" target="_blank">Google Maps</a>, desni klik na tačnu lokaciju nekretnine, klikni na prvi broj (npr. "44.7722, 17.1910") da ga kopiraš, pa nalepi prvi broj u "lat" a drugi u "lng". Ako ostaviš prazno, mapa se neće prikazati u detaljima.
  </p>

  <div class="form-row">
    <div class="field">
      <label>Kvadratura (m²)</label>
      <input type="number" name="kvadratura" step="0.01" value="<?= clean((string)$nekretnina['kvadratura']) ?>">
    </div>
    <div class="field">
      <label>Broj soba (0 ako nije primenjivo)</label>
      <input type="number" name="sobe" value="<?= clean((string)$nekretnina['sobe']) ?>">
    </div>
  </div>

  <div class="field">
    <label>Opis</label>
    <textarea name="opis" rows="5"><?= clean($nekretnina['opis']) ?></textarea>
  </div>

  <div class="form-row">
    <label class="checkbox-label">
      <input type="checkbox" name="istaknuta" <?= $nekretnina['istaknuta'] ? 'checked' : '' ?>> Istaknuta nekretnina (zlatna oznaka)
    </label>
    <label class="checkbox-label">
      <input type="checkbox" name="aktivna" <?= $nekretnina['aktivna'] ? 'checked' : '' ?>> Aktivna (prikazana na sajtu)
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
              <img src="../uploads/nekretnine/<?= clean($s['fajl']) ?>" alt="" style="width:100%;height:90px;object-fit:cover;border-radius:4px;margin-bottom:6px;display:block;">
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
    <p class="field-hint">Savet: da izabereš više slika odjednom, u prozoru za biranje fajlova drži <strong>Ctrl</strong> (Windows) ili <strong>Cmd</strong> (Mac) i klikni na svaku sliku koju želiš da dodaš.</p>
  </div>

  <div class="field">
    <label>Dodaj video u galeriju (MP4, max <?= (int)(MAX_VIDEO_SIZE / 1024 / 1024) ?>MB)</label>
    <input type="file" name="video_fajl" accept="video/mp4">
    <p class="field-hint">Video će se prikazati među slikama u galeriji — posetilac ga listaju zajedno sa fotografijama. Nova video zamenjuje postojeći ako ga dodaš ponovo (obriši stari prvo iz galerije iznad ako ga ne želiš duplo).</p>
  </div>

  <div class="field">
    <label>Video link (YouTube, Vimeo ili Instagram) — opciono, prikazuje se kao posebna sekcija</label>
    <input type="text" name="video_url" placeholder="https://www.youtube.com/watch?v=... ili https://www.instagram.com/reel/..." value="<?= clean($nekretnina['video_url']) ?>">
  </div>

  <div class="field">
    <label>Dokument (PDF — tlocrt, specifikacija i sl.)</label>
    <?php if (!empty($nekretnina['dokument'])): ?>
      <p class="field-hint">
        Trenutni dokument: <a href="../uploads/dokumenti/<?= clean($nekretnina['dokument']) ?>" target="_blank">preuzmi / pogledaj</a>
        &nbsp;·&nbsp;
        <label class="checkbox-label small"><input type="checkbox" name="ukloni_dokument" value="1"> Ukloni dokument</label>
      </p>
    <?php endif; ?>
    <input type="file" name="dokument" accept="application/pdf">
  </div>

  <div class="form-actions">
    <button type="submit" class="btn-primary"><?= $editing ? 'Sačuvaj izmene' : 'Dodaj nekretninu' ?></button>
    <a href="dashboard.php" class="btn-secondary">Otkaži</a>
  </div>
</form>

<?php include __DIR__ . '/includes/footer.php'; ?>
