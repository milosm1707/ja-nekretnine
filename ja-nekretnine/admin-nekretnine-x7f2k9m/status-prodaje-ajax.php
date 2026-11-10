<?php
require_once __DIR__ . '/../includes/bootstrap.php';
header('Content-Type: application/json');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Niste prijavljeni.']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Nevažeći zahtev.']);
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$status = $_POST['status_prodaje'] ?? '';

if (!$id || !in_array($status, ['Slobodno', 'Rezervisano', 'Prodato'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Neispravni podaci.']);
    exit;
}

$pdo = getDB();
$stmt = $pdo->prepare('UPDATE nekretnine SET status_prodaje = ? WHERE id = ? AND tip = ?');
$stmt->execute([$status, $id, 'Prodaja']);

echo json_encode(['success' => true]);
