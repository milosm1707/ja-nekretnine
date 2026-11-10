<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify()) {
    flash_set('error', 'Nevažeći zahtev.');
    redirect('zgrade-dashboard.php');
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    redirect('zgrade-dashboard.php');
}

$pdo = getDB();

$stmt = $pdo->prepare('SELECT fajl FROM slike_zgrada WHERE zgrada_id = ?');
$stmt->execute([$id]);
$slike = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT dokument FROM zgrade WHERE id = ?');
$stmt->execute([$id]);
$dokument = $stmt->fetchColumn();

// nekretnine.zgrada_id se automatski postavlja na NULL (ON DELETE SET NULL) - stanovi ostaju
$stmt = $pdo->prepare('DELETE FROM zgrade WHERE id = ?');
$stmt->execute([$id]);

foreach ($slike as $s) {
    delete_image_file($s['fajl'], UPLOAD_ZGRADE_DIR);
}
if ($dokument) {
    delete_document_file($dokument);
}

flash_set('success', 'Zgrada je obrisana. Povezani stanovi su zadržani, samo više nisu vezani ni za jednu zgradu.');
redirect('zgrade-dashboard.php');
