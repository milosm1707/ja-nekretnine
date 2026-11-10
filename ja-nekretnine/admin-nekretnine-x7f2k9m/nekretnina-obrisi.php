<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify()) {
    flash_set('error', 'Nevažeći zahtev.');
    redirect('dashboard.php');
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    redirect('dashboard.php');
}

$pdo = getDB();

// pokupi slike da ih obrišemo sa diska pre brisanja reda (FK CASCADE briše same redove u bazi)
$stmt = $pdo->prepare('SELECT fajl FROM slike_nekretnina WHERE nekretnina_id = ?');
$stmt->execute([$id]);
$slike = $stmt->fetchAll();

$stmt = $pdo->prepare('DELETE FROM nekretnine WHERE id = ?');
$stmt->execute([$id]);

foreach ($slike as $s) {
    delete_image_file($s['fajl']);
}

flash_set('success', 'Nekretnina je obrisana.');
redirect('dashboard.php');
