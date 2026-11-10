<?php
// ============================================================
// DIJAGNOSTIKA SLIKA
// Pokaži koje slike baza očekuje vs koje slike stvarno postoje
// na disku, da se tačno vidi gde je nesklad.
//
// Otvori: https://tvojsajt.com/setup/dijagnostika-slika.php
// Obriši ovaj fajl kad završiš dijagnostiku.
// ============================================================

require_once __DIR__ . '/../config/database.php';

$pdo = getDB();

function listaj_fajlove(string $dir): array {
    if (!is_dir($dir)) return [];
    $fajlovi = [];
    foreach (scandir($dir) as $f) {
        if ($f === '.' || $f === '..' || $f === '.htaccess' || $f === '.gitkeep') continue;
        $fajlovi[] = $f;
    }
    return $fajlovi;
}

// fajlovi koje baza referiše
$bazaNekretnine = [];
foreach ($pdo->query('SELECT naslovna_slika FROM nekretnine WHERE naslovna_slika IS NOT NULL') as $r) {
    $bazaNekretnine[$r['naslovna_slika']] = true;
}
foreach ($pdo->query('SELECT fajl FROM slike_nekretnina') as $r) {
    $bazaNekretnine[$r['fajl']] = true;
}
$bazaNekretnine = array_keys($bazaNekretnine);

$bazaZgrade = [];
foreach ($pdo->query('SELECT naslovna_slika FROM zgrade WHERE naslovna_slika IS NOT NULL') as $r) {
    $bazaZgrade[$r['naslovna_slika']] = true;
}
foreach ($pdo->query('SELECT fajl FROM slike_zgrada') as $r) {
    $bazaZgrade[$r['fajl']] = true;
}
$bazaZgrade = array_keys($bazaZgrade);

// fajlovi koji stvarno postoje na disku
$diskNekretnine = listaj_fajlove(UPLOAD_DIR);
$diskZgrade = listaj_fajlove(UPLOAD_ZGRADE_DIR);

// nesklad
$nedostajuNekretnine = array_diff($bazaNekretnine, $diskNekretnine);
$nedostajuZgrade = array_diff($bazaZgrade, $diskZgrade);
$visakNaDiskuNekretnine = array_diff($diskNekretnine, $bazaNekretnine);
$visakNaDiskuZgrade = array_diff($diskZgrade, $bazaZgrade);

function clean($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="sr">
<head>
<meta charset="UTF-8">
<title>Dijagnostika slika</title>
<meta name="robots" content="noindex, nofollow">
<style>
  body { font-family: Arial, sans-serif; background:#141414; color:#ddd; margin:0; padding:30px; }
  .box { max-width:900px; margin:0 auto; }
  h1 { color:#fff; }
  h2 { color:#fff; margin-top:36px; font-size:16px; border-bottom:1px solid #333; padding-bottom:8px; }
  .ok { background:#16321a; border:1px solid #2a7a3a; padding:14px; border-radius:6px; color:#b3ffb8; }
  .problem { background:#3a1616; border:1px solid #7a2a2a; padding:14px; border-radius:6px; color:#ffb3b3; margin-bottom:10px; }
  .info { background:#1c1c1c; border:1px solid #333; padding:14px; border-radius:6px; }
  code { background:#0d0d0d; padding:2px 6px; border-radius:3px; color:#7ab33a; }
  .path { color:#888; font-size:12px; margin-bottom:16px; }
  .stat { display:inline-block; margin-right:24px; font-size:14px; }
  .stat strong { color:#7ab33a; }
  ul { line-height:1.8; }
</style>
</head>
<body>
<div class="box">
  <h1>Dijagnostika slika</h1>
  <p class="path">
    Nekretnine folder: <code><?= clean(UPLOAD_DIR) ?></code><br>
    Zgrade folder: <code><?= clean(UPLOAD_ZGRADE_DIR) ?></code>
  </p>

  <div class="info">
    <span class="stat">Baza očekuje (nekretnine): <strong><?= count($bazaNekretnine) ?></strong></span>
    <span class="stat">Na disku (nekretnine): <strong><?= count($diskNekretnine) ?></strong></span>
    <br><br>
    <span class="stat">Baza očekuje (zgrade): <strong><?= count($bazaZgrade) ?></strong></span>
    <span class="stat">Na disku (zgrade): <strong><?= count($diskZgrade) ?></strong></span>
  </div>

  <h2>Slike nekretnina koje baza traži ali NE POSTOJE na disku (uzrok 404 grešaka)</h2>
  <?php if (empty($nedostajuNekretnine)): ?>
    <div class="ok">Sve slike koje baza referiše postoje na disku. Nema problema ovde.</div>
  <?php else: ?>
    <div class="problem">
      Nedostaje <?= count($nedostajuNekretnine) ?> od <?= count($bazaNekretnine) ?> fajlova:
      <ul><?php foreach ($nedostajuNekretnine as $f): ?><li><code><?= clean($f) ?></code></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <h2>Slike zgrada koje baza traži ali NE POSTOJE na disku</h2>
  <?php if (empty($nedostajuZgrade)): ?>
    <div class="ok">Sve slike koje baza referiše postoje na disku. Nema problema ovde.</div>
  <?php else: ?>
    <div class="problem">
      Nedostaje <?= count($nedostajuZgrade) ?> od <?= count($bazaZgrade) ?> fajlova:
      <ul><?php foreach ($nedostajuZgrade as $f): ?><li><code><?= clean($f) ?></code></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <h2>Fajlovi na disku koje baza NE spominje (siroti fajlovi, bezopasno)</h2>
  <p style="color:#888;font-size:13px">Ovo su fajlovi koji fizički postoje ali ih nijedna nekretnina/zgrada trenutno ne koristi — obično od ranijih testova. Ne utiču na 404 greške, možeš ih ignorisati ili obrisati ručno.</p>
  <div class="info">
    Nekretnine: <?= count($visakNaDiskuNekretnine) ?> fajlova &nbsp;·&nbsp; Zgrade: <?= count($visakNaDiskuZgrade) ?> fajlova
  </div>

</div>
</body>
</html>
