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

$nekretnina_id = (int)($_POST['nekretnina_id'] ?? 0);
$promeneRaw = $_POST['promene'] ?? '';

if (!$nekretnina_id || $promeneRaw === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Neispravni podaci.']);
    exit;
}

$promene = json_decode($promeneRaw, true);
if (!is_array($promene) || empty($promene)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Nema izmena za čuvanje.']);
    exit;
}

$pdo = getDB();

// proveri da nekretnina zaista postoji
$stmt = $pdo->prepare('SELECT tip FROM nekretnine WHERE id = ?');
$stmt->execute([$nekretnina_id]);
$tip = $stmt->fetchColumn();
if (!$tip) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Nekretnina ne postoji.']);
    exit;
}

// validiraj svaku izmenu pre nego što bilo šta upišemo
$validne = [];
foreach ($promene as $p) {
    $datum = $p['datum'] ?? '';
    $status = $p['status'] ?? '';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $datum) || !in_array($status, ['slobodno', 'zauzeto'], true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Neispravan format jedne od izmena.']);
        exit;
    }
    $validne[] = ['datum' => $datum, 'status' => $status];
}

try {
    $pdo->beginTransaction();

    $insert = $pdo->prepare('INSERT INTO dostupnost (nekretnina_id, datum, status) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE status = VALUES(status)');
    $delete = $pdo->prepare('DELETE FROM dostupnost WHERE nekretnina_id = ? AND datum = ?');

    foreach ($validne as $p) {
        if ($p['status'] === 'slobodno') {
            $delete->execute([$nekretnina_id, $p['datum']]);
        } else {
            $insert->execute([$nekretnina_id, $p['datum'], $p['status']]);
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'sacuvano' => count($validne)]);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Greška pri čuvanju u bazu.']);
}
