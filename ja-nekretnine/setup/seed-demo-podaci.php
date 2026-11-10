<?php
// ============================================================
// JEDNOKRATNA SKRIPTA za punjenje sajta demo podacima.
//
// PAŽNJA: ovo prvo BRIŠE SVE postojeće nekretnine i zgrade
// (i njihove slike/dokumente sa diska), pa tek onda dodaje:
//   - 2 zgrade, svaka sa po 10 stanova
//   - 10 samostalnih nekretnina (kuće i poslovni prostori),
//     koje ne pripadaju nijednoj zgradi
// Ukupno 30 nekretnina + 2 zgrade.
//
// KORACI:
// 1. Otvori u browseru: https://tvojsajt.com/setup/seed-demo-podaci.php
// 2. Klikni "Obriši sve i dodaj demo podatke"
// 3. Sačekaj 1-3 minuta dok se preuzimaju slike
// 4. Kad završiš prezentaciju, obriši ovaj fajl sa servera.
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = getDB();

/**
 * Preuzima sliku sa URL-a, proverava da je zaista slika,
 * ponovo je iscrtava (briše metapodatke) i snima pod nasumičnim imenom
 * u zadati folder. Vraća ime fajla ili null ako preuzimanje ne uspe.
 */
function preuzmi_i_sacuvaj_sliku(string $url, string $targetDir): ?string {
    $context = stream_context_create([
        'http' => ['timeout' => 10, 'follow_location' => 1, 'header' => "User-Agent: Mozilla/5.0\r\n"],
        'ssl'  => ['verify_peer' => true, 'verify_peer_name' => true],
    ]);

    $data = @file_get_contents($url, false, $context);
    if ($data === false || strlen($data) < 500) {
        return null;
    }

    $imgInfo = @getimagesizefromstring($data);
    if ($imgInfo === false) {
        return null;
    }

    $image = @imagecreatefromstring($data);
    if ($image === false) {
        return null;
    }

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $filename = bin2hex(random_bytes(16)) . '.jpg';
    $destination = $targetDir . $filename;

    $ok = imagejpeg($image, $destination, 85);
    imagedestroy($image);

    return $ok ? $filename : null;
}

/** Doda malu nasumičnu varijaciju koordinatama da nekretnine u istom gradu ne budu na tačno istoj tački. */
function jitter(float $coord): float {
    return $coord + (mt_rand(-80, 80) / 10000);
}

// gradovi sa realnim koordinatama (za mapu)
$gradovi = [
    'Banja Luka' => [44.7722, 17.1910],
    'Sarajevo'   => [43.8563, 18.4131],
    'Mostar'     => [43.3438, 17.8078],
    'Tuzla'      => [44.5386, 18.6742],
    'Trebinje'   => [42.7105, 18.3437],
    'Bijeljina'  => [44.7581, 19.2144],
];

$done = false;
$log = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['potvrdi'])) {

    // ── 1. BRISANJE POSTOJEĆIH PODATAKA ──
    $stmt = $pdo->query('SELECT fajl FROM slike_nekretnina');
    foreach ($stmt->fetchAll() as $s) delete_image_file($s['fajl'], UPLOAD_DIR);

    $stmt = $pdo->query('SELECT fajl FROM slike_zgrada');
    foreach ($stmt->fetchAll() as $s) delete_image_file($s['fajl'], UPLOAD_ZGRADE_DIR);

    $stmt = $pdo->query('SELECT dokument FROM zgrade WHERE dokument IS NOT NULL');
    foreach ($stmt->fetchAll() as $z) delete_document_file($z['dokument']);

    $pdo->exec('DELETE FROM nekretnine');
    $pdo->exec('DELETE FROM zgrade');
    $pdo->exec('ALTER TABLE nekretnine AUTO_INCREMENT = 1');
    $pdo->exec('ALTER TABLE zgrade AUTO_INCREMENT = 1');
    $pdo->exec('ALTER TABLE slike_nekretnina AUTO_INCREMENT = 1');
    $pdo->exec('ALTER TABLE slike_zgrada AUTO_INCREMENT = 1');

    $log[] = 'Obrisane su sve prethodne nekretnine i zgrade.';

    // ── 2. DVE ZGRADE ──
    $zgradeDef = [
        [
            'naziv' => 'Rezidencija Vidikovac', 'grad' => 'Banja Luka',
            'adresa' => 'Bulevar Srpske vojske 24', 'godina' => 2023, 'spratova' => 10,
            'opis' => 'Moderan stambeni kompleks u mirnom delu grada, sa podzemnom garažom i uređenim dvorištem.',
            'tag' => 'apartment,building,modern',
        ],
        [
            'naziv' => 'Sunčani Park Residence', 'grad' => 'Sarajevo',
            'adresa' => 'Ulica Grbavička 12', 'godina' => 2022, 'spratova' => 8,
            'opis' => 'Stambeni objekat visokog standarda blizu centra grada, energetski efikasna gradnja.',
            'tag' => 'apartment,building,city',
        ],
    ];

    $zgradaIds = [];
    foreach ($zgradeDef as $i => $z) {
        [$lat, $lng] = $gradovi[$z['grad']];
        $stmt = $pdo->prepare('INSERT INTO zgrade (naziv, opis, lokacija, adresa, lat, lng, godina_izgradnje, broj_spratova, istaknuta, aktivna) VALUES (?,?,?,?,?,?,?,?,?,1)');
        $stmt->execute([$z['naziv'], $z['opis'], $z['grad'], $z['adresa'], jitter($lat), jitter($lng), $z['godina'], $z['spratova'], $i === 0 ? 1 : 0]);
        $zid = (int)$pdo->lastInsertId();
        $zgradaIds[] = $zid;

        $slikaBroj = 0;
        for ($k = 0; $k < 2; $k++) {
            $url = "https://loremflickr.com/900/600/{$z['tag']}?lock=" . ($zid * 100 + $k);
            $fn = preuzmi_i_sacuvaj_sliku($url, UPLOAD_ZGRADE_DIR);
            if ($fn) {
                $stmt = $pdo->prepare('INSERT INTO slike_zgrada (zgrada_id, fajl, redosled) VALUES (?, ?, ?)');
                $stmt->execute([$zid, $fn, $k]);
                $slikaBroj++;
            }
        }
        if ($slikaBroj > 0) {
            $stmt = $pdo->prepare('SELECT fajl FROM slike_zgrada WHERE zgrada_id = ? ORDER BY redosled ASC LIMIT 1');
            $stmt->execute([$zid]);
            $prva = $stmt->fetchColumn();
            $pdo->prepare('UPDATE zgrade SET naslovna_slika = ? WHERE id = ?')->execute([$prva, $zid]);
        }
        $log[] = "Zgrada \"{$z['naziv']}\" dodata ({$slikaBroj} slika).";
    }

    // ── 3. PO 10 STANOVA ZA SVAKU ZGRADU ──
    $rentIzdav = ['Prodaja', 'Prodaja', 'Prodaja', 'Iznajmljivanje', 'Prodaja', 'Prodaja', 'Iznajmljivanje', 'Prodaja', 'Prodaja', 'Iznajmljivanje'];
    $spratovi = ['prizemlje', '1. sprat', '2. sprat', '3. sprat', '4. sprat', '5. sprat', '6. sprat', '7. sprat', '8. sprat', 'potkrovlje'];
    $kvadrature = [45, 52, 58, 62, 68, 74, 80, 85, 92, 105];
    $sobeArr = [1, 2, 2, 2, 3, 3, 3, 3, 4, 4];

    $rentniZaKalendar = [];

    foreach ($zgradaIds as $zi => $zid) {
        $z = $zgradeDef[$zi];
        [$lat, $lng] = $gradovi[$z['grad']];

        for ($s = 0; $s < 10; $s++) {
            $tip = $rentIzdav[$s];
            $kv = $kvadrature[$s];
            $sobe = $sobeArr[$s];
            $naziv = "Stan " . ($s + 1) . "0{$s} — {$spratovi[$s]}, {$z['naziv']}";
            $cijena = $tip === 'Prodaja'
                ? number_format(55000 + $kv * 900, 0, ',', '.') . ' KM'
                : number_format(300 + $kv * 6, 0, ',', '.') . ' KM/mj';

            $stmt = $pdo->prepare('INSERT INTO nekretnine (naziv, opis, tip, kategorija, zgrada_id, cijena, lokacija, lat, lng, kvadratura, sobe, istaknuta, aktivna) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,1)');
            $stmt->execute([
                $naziv,
                "Stan na {$spratovi[$s]} u zgradi \"{$z['naziv']}\". {$kv} m², {$sobe} sobe.",
                $tip, 'Stan', $zid, $cijena, $z['grad'], jitter($lat), jitter($lng), $kv, $sobe,
                ($s === 0) ? 1 : 0,
            ]);
            $nid = (int)$pdo->lastInsertId();

            $slikaBroj = 0;
            for ($k = 0; $k < 2; $k++) {
                $url = "https://loremflickr.com/900/600/apartment,interior?lock=" . ($nid * 100 + $k);
                $fn = preuzmi_i_sacuvaj_sliku($url, UPLOAD_DIR);
                if ($fn) {
                    $stmt = $pdo->prepare('INSERT INTO slike_nekretnina (nekretnina_id, fajl, redosled) VALUES (?, ?, ?)');
                    $stmt->execute([$nid, $fn, $k]);
                    $slikaBroj++;
                }
            }
            if ($slikaBroj > 0) {
                $stmt = $pdo->prepare('SELECT fajl FROM slike_nekretnina WHERE nekretnina_id = ? ORDER BY redosled ASC LIMIT 1');
                $stmt->execute([$nid]);
                $prva = $stmt->fetchColumn();
                $pdo->prepare('UPDATE nekretnine SET naslovna_slika = ? WHERE id = ?')->execute([$prva, $nid]);
            }

            if ($tip === 'Iznajmljivanje') {
                $rentniZaKalendar[] = $nid;
            }

            $log[] = "  {$naziv} ({$slikaBroj} slika)";
        }
    }

    // ── 4. 10 SAMOSTALNIH NEKRETNINA (bez zgrade) — kuće i poslovni prostori ──
    $samostalne = [
        ['Porodična kuća sa dvorištem', 'Prostrana kuća sa ograđenim dvorištem i garažom za dva vozila.', 'Prodaja', 'Kuca', '285.000 KM', 'Banja Luka', 220, 5, 1, 'house,exterior'],
        ['Vikendica na mirnoj lokaciji', 'Manja vikendica okružena zelenilom, idealna za odmor van grada.', 'Prodaja', 'Kuca', '95.000 KM', 'Trebinje', 90, 3, 0, 'cottage,countryside'],
        ['Kuća sa okućnicom', 'Kuća na velikom placu sa voćnjakom i pomoćnim objektima.', 'Prodaja', 'Kuca', '150.000 KM', 'Bijeljina', 180, 5, 0, 'house,garden'],
        ['Moderna kuća sa bazenom', 'Novoizgrađena moderna kuća sa spoljnim bazenom i vrtnim uređenjem.', 'Prodaja', 'Kuca', '310.000 KM', 'Mostar', 240, 5, 1, 'modern-house,pool'],
        ['Kuća za iznajmljivanje sa baštom', 'Porodična kuća sa baštom i terasom, mirna ulica.', 'Iznajmljivanje', 'Kuca', '650 KM/mj', 'Tuzla', 150, 4, 0, 'house,garden,family'],
        ['Vila sa bazenom za odmor', 'Ekskluzivna vila sa privatnim bazenom, pogodna za duži boravak.', 'Iznajmljivanje', 'Kuca', '1.100 KM/mj', 'Trebinje', 250, 5, 1, 'villa,pool,luxury'],
        ['Poslovni prostor u centru grada', 'Reprezentativan poslovni prostor u prizemlju, izlog na glavnoj ulici.', 'Iznajmljivanje', 'Poslovni prostor', '1.200 KM/mj', 'Sarajevo', 60, 0, 1, 'office,workspace'],
        ['Lokal u prizemlju zgrade', 'Manji poslovni prostor, prometna lokacija, izlog prema ulici.', 'Iznajmljivanje', 'Poslovni prostor', '700 KM/mj', 'Banja Luka', 40, 0, 0, 'store,retail'],
        ['Magacinski prostor sa rampom', 'Veliki magacinski prostor sa utovarnom rampom, pogodan za distribuciju.', 'Prodaja', 'Poslovni prostor', '180.000 KM', 'Tuzla', 300, 0, 0, 'warehouse,industrial'],
        ['Kancelarijski prostor na spratu', 'Prostrane kancelarije na prvom spratu poslovne zgrade, klima, parking.', 'Prodaja', 'Poslovni prostor', '210.000 KM', 'Sarajevo', 120, 0, 0, 'office,corporate'],
    ];

    foreach ($samostalne as $sN) {
        [$naziv, $opis, $tip, $kategorija, $cijena, $grad, $kv, $sobe, $istaknuta, $tag] = $sN;
        [$lat, $lng] = $gradovi[$grad];

        $stmt = $pdo->prepare('INSERT INTO nekretnine (naziv, opis, tip, kategorija, zgrada_id, cijena, lokacija, lat, lng, kvadratura, sobe, istaknuta, aktivna) VALUES (?,?,?,?,NULL,?,?,?,?,?,?,?,1)');
        $stmt->execute([$naziv, $opis, $tip, $kategorija, $cijena, $grad, jitter($lat), jitter($lng), $kv, $sobe, $istaknuta]);
        $nid = (int)$pdo->lastInsertId();

        $slikaBroj = 0;
        for ($k = 0; $k < 2; $k++) {
            $url = "https://loremflickr.com/900/600/{$tag}?lock=" . ($nid * 100 + $k);
            $fn = preuzmi_i_sacuvaj_sliku($url, UPLOAD_DIR);
            if ($fn) {
                $stmt = $pdo->prepare('INSERT INTO slike_nekretnina (nekretnina_id, fajl, redosled) VALUES (?, ?, ?)');
                $stmt->execute([$nid, $fn, $k]);
                $slikaBroj++;
            }
        }
        if ($slikaBroj > 0) {
            $stmt = $pdo->prepare('SELECT fajl FROM slike_nekretnina WHERE nekretnina_id = ? ORDER BY redosled ASC LIMIT 1');
            $stmt->execute([$nid]);
            $prva = $stmt->fetchColumn();
            $pdo->prepare('UPDATE nekretnine SET naslovna_slika = ? WHERE id = ?')->execute([$prva, $nid]);
        }

        if ($tip === 'Iznajmljivanje') {
            $rentniZaKalendar[] = $nid;
        }

        $log[] = "{$naziv} ({$slikaBroj} slika)";
    }

    // ── 5. demo kalendar za par nekretnina za iznajmljivanje ──
    $izabraniZaKalendar = array_slice($rentniZaKalendar, 0, 3);
    foreach ($izabraniZaKalendar as $nid) {
        $pocetak = new DateTime('+5 days');
        for ($d = 0; $d < 4; $d++) {
            $datum = (clone $pocetak)->modify("+{$d} days")->format('Y-m-d');
            $stmt = $pdo->prepare('INSERT INTO dostupnost (nekretnina_id, datum, status) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE status = VALUES(status)');
            $stmt->execute([$nid, $datum, 'zauzeto']);
        }
    }

    $done = true;
}

$brojNekretnina = (int)$pdo->query('SELECT COUNT(*) FROM nekretnine')->fetchColumn();
$brojZgrada = (int)$pdo->query('SELECT COUNT(*) FROM zgrade')->fetchColumn();
?>
<!DOCTYPE html>
<html lang="sr">
<head>
<meta charset="UTF-8">
<title>Demo podaci — JA Nekretnine</title>
<meta name="robots" content="noindex, nofollow">
<style>
  body { font-family: Arial, sans-serif; background:#141414; color:#ddd; display:flex; align-items:center; justify-content:center; min-height:100vh; margin:0; padding:20px; }
  .box { background:#1c1c1c; padding:40px; border-radius:8px; max-width:560px; width:100%; }
  h1 { font-size:20px; margin-bottom:16px; color:#fff; }
  p { font-size:14px; line-height:1.6; color:#b8b8b0; }
  button { margin-top:20px; width:100%; padding:12px; background:#c0392b; border:none; border-radius:4px; color:#fff; font-weight:bold; cursor:pointer; font-size:15px; }
  .success { background:#16321a; border:1px solid #2a7a3a; padding:12px; border-radius:4px; margin-bottom:16px; color:#b3ffb8; font-size:14px; }
  .warn-box { background:#3a2c16; border:1px solid #7a5a2a; padding:12px; border-radius:4px; margin-bottom:16px; color:#ffdcae; font-size:14px; }
  .log { max-height:320px; overflow-y:auto; font-size:12.5px; margin-top:16px; border-top:1px solid #333; padding-top:16px; line-height:1.8; }
  .warn { font-size:13px; color:#b8b8b0; margin-top:20px; line-height:1.5; }
  .stats { display:flex; gap:20px; margin-bottom:16px; }
  .stats div { background:#0d0d0d; padding:12px 16px; border-radius:6px; flex:1; text-align:center; }
  .stats strong { display:block; font-size:22px; color:#7ab33a; }
</style>
</head>
<body>
<div class="box">
  <h1>Demo podaci za prezentaciju</h1>

  <?php if ($done): ?>
    <div class="success">Gotovo! Baza je očišćena i ponovo napunjena demo podacima.</div>
    <div class="stats">
      <div><strong><?= $brojZgrada ?></strong>zgrade</div>
      <div><strong><?= $brojNekretnina ?></strong>nekretnine</div>
    </div>
    <div class="log"><?php foreach ($log as $l): ?><div><?= clean($l) ?></div><?php endforeach; ?></div>
    <p class="warn">
      Ako neka nekretnina ima "0 slika", server verovatno nema izlazni internet pristup u tom trenutku — nekretnina i dalje radi, samo bez fotografije.<br><br>
      Kad završiš prezentaciju: obriši ove test podatke (pokreni ovu skriptu ponovo) i obriši <code>setup/seed-demo-podaci.php</code> sa servera.
    </p>
  <?php else: ?>
    <div class="warn-box">
      <strong>Pažnja:</strong> ovo će prvo obrisati SVE postojeće nekretnine i zgrade (i njihove slike sa diska), pa tek onda dodati novih 30 nekretnina + 2 zgrade. Ne može se poništiti.
    </div>
    <p>Trenutno u bazi: <strong><?= $brojNekretnina ?></strong> nekretnina, <strong><?= $brojZgrada ?></strong> zgrada.</p>
    <p>Posle potvrde dodaje se: 2 zgrade (svaka sa po 10 stanova) + 10 samostalnih nekretnina (kuće i poslovni prostori) = 30 nekretnina ukupno. Traje 1-3 minuta zbog preuzimanja slika.</p>
    <form method="post">
      <input type="hidden" name="potvrdi" value="1">
      <button type="submit">Obriši sve i dodaj demo podatke</button>
    </form>
  <?php endif; ?>
</div>
</body>
</html>
