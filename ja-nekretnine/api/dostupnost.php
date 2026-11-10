<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json; charset=utf-8');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Nevažeći ID.']);
    exit;
}

$pdo = getDB();

$stmt = $pdo->prepare('SELECT tip FROM nekretnine WHERE id = ? AND aktivna = 1');
$stmt->execute([$id]);
$tip = $stmt->fetchColumn();

if (!$tip) {
    http_response_code(404);
    echo json_encode(['error' => 'Nekretnina nije pronađena.']);
    exit;
}
if ($tip !== 'Iznajmljivanje') {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("SELECT datum, status FROM dostupnost WHERE nekretnina_id = ? AND datum >= CURDATE()");
$stmt->execute([$id]);

$out = [];
foreach ($stmt->fetchAll() as $row) {
    $out[$row['datum']] = $row['status'];
}

echo json_encode($out, JSON_UNESCAPED_UNICODE);
