<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

$pdo = getDB();
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM nekretnine WHERE id = ?');
$stmt->execute([$id]);
$nekretnina = $stmt->fetch();

if (!$nekretnina) {
    flash_set('error', 'Nekretnina nije pronađena.');
    redirect('dashboard.php');
}
if ($nekretnina['tip'] !== 'Iznajmljivanje') {
    flash_set('error', 'Kalendar dostupnosti postoji samo za nekretnine za iznajmljivanje.');
    redirect('dashboard.php');
}

// dohvati sve zauzete datume za sledećih 6 meseci
$stmt = $pdo->prepare("SELECT datum, status FROM dostupnost WHERE nekretnina_id = ? AND datum >= CURDATE()");
$stmt->execute([$id]);
$statusi = [];
foreach ($stmt->fetchAll() as $row) {
    $statusi[$row['datum']] = $row['status'];
}

$page_title = 'Kalendar — ' . $nekretnina['naziv'];
include __DIR__ . '/includes/header.php';
?>

<h1>Kalendar dostupnosti</h1>
<p class="admin-sub"><?= clean($nekretnina['naziv']) ?> — klikni na dan da promeniš status, pa klikni "Sačuvaj izmene" da se sačuva.</p>

<div class="calendar-legend">
  <span class="legend-item"><span class="dot dot--free"></span> Slobodno</span>
  <span class="legend-item"><span class="dot dot--taken"></span> Zauzeto</span>
  <span class="legend-item"><span class="dot dot--pending"></span> Nesačuvana izmena</span>
</div>

<div id="calendarWrap" class="calendar-months" data-nekretnina-id="<?= $id ?>"></div>

<div class="calendar-actions">
  <button id="sacuvajKalendar" class="btn-primary" disabled>Sačuvaj izmene</button>
  <a href="dashboard.php" class="btn-secondary">← Nazad na nekretnine</a>
  <span id="kalendarStatus" class="calendar-status"></span>
</div>

<script>
  const NEKRETNINA_ID = <?= $id ?>;
  const STATUSI = <?= json_encode($statusi, JSON_UNESCAPED_UNICODE) ?>;
  const CSRF_TOKEN = <?= json_encode(csrf_token()) ?>;
</script>
<script src="assets/kalendar.js?v=3"></script>

<?php include __DIR__ . '/includes/footer.php'; ?>
