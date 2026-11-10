<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json; charset=utf-8');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Nevažeći ID.']);
    exit;
}

$pdo = getDB();
$stmt = $pdo->prepare('SELECT * FROM nekretnine WHERE id = ? AND aktivna = 1');
$stmt->execute([$id]);
$n = $stmt->fetch();

if (!$n) {
    http_response_code(404);
    echo json_encode(['error' => 'Nekretnina nije pronađena.']);
    exit;
}

$stmt = $pdo->prepare('SELECT fajl, tip FROM slike_nekretnina WHERE nekretnina_id = ? ORDER BY redosled ASC, id ASC');
$stmt->execute([$id]);
$slike = array_map(function ($r) {
    return [
        'tip' => $r['tip'],
        'url' => UPLOAD_URL . $r['fajl'],
    ];
}, $stmt->fetchAll());

echo json_encode([
    'id'         => (int)$n['id'],
    'naziv'      => $n['naziv'],
    'opis'       => $n['opis'],
    'tip'        => $n['tip'],
    'status_prodaje' => $n['status_prodaje'],
    'kategorija' => $n['kategorija'],
    'cijena'     => $n['cijena'],
    'lokacija'   => $n['lokacija'],
    'lat'        => $n['lat'] !== null ? (float)$n['lat'] : null,
    'lng'        => $n['lng'] !== null ? (float)$n['lng'] : null,
    'kvadratura' => (float)$n['kvadratura'],
    'sobe'       => (int)$n['sobe'],
    'istaknuta'  => (bool)$n['istaknuta'],
    'slike'      => $slike,
    'dokument'   => $n['dokument'] ? UPLOAD_DOC_URL . $n['dokument'] : null,
    'video_embed' => video_embed_info($n['video_url']),
], JSON_UNESCAPED_UNICODE);
