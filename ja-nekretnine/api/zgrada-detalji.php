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
$stmt = $pdo->prepare('SELECT * FROM zgrade WHERE id = ? AND aktivna = 1');
$stmt->execute([$id]);
$z = $stmt->fetch();

if (!$z) {
    http_response_code(404);
    echo json_encode(['error' => 'Zgrada nije pronađena.']);
    exit;
}

$stmt = $pdo->prepare('SELECT fajl, tip FROM slike_zgrada WHERE zgrada_id = ? ORDER BY redosled ASC, id ASC');
$stmt->execute([$id]);
$slike = array_map(function ($r) {
    return [
        'tip' => $r['tip'],
        'url' => UPLOAD_ZGRADE_URL . $r['fajl'],
    ];
}, $stmt->fetchAll());

$stmt = $pdo->prepare("SELECT id, naziv, tip, status_prodaje, kategorija, cijena, kvadratura, sobe, naslovna_slika
                        FROM nekretnine WHERE zgrada_id = ? AND aktivna = 1 ORDER BY naziv ASC");
$stmt->execute([$id]);
$stanovi = array_map(function ($n) {
    return [
        'id'         => (int)$n['id'],
        'naziv'      => $n['naziv'],
        'tip'        => $n['tip'],
        'status_prodaje' => $n['status_prodaje'],
        'kategorija' => $n['kategorija'],
        'cijena'     => $n['cijena'],
        'kvadratura' => (float)$n['kvadratura'],
        'sobe'       => (int)$n['sobe'],
        'slika'      => $n['naslovna_slika'] ? UPLOAD_URL . $n['naslovna_slika'] : '',
    ];
}, $stmt->fetchAll());

echo json_encode([
    'id'               => (int)$z['id'],
    'naziv'            => $z['naziv'],
    'opis'             => $z['opis'],
    'lokacija'         => $z['lokacija'],
    'adresa'           => $z['adresa'],
    'lat'              => $z['lat'] !== null ? (float)$z['lat'] : null,
    'lng'              => $z['lng'] !== null ? (float)$z['lng'] : null,
    'godina_izgradnje' => $z['godina_izgradnje'] ? (int)$z['godina_izgradnje'] : null,
    'broj_spratova'    => $z['broj_spratova'] ? (int)$z['broj_spratova'] : null,
    'istaknuta'        => (bool)$z['istaknuta'],
    'slike'            => $slike,
    'dokument'         => $z['dokument'] ? UPLOAD_DOC_URL . $z['dokument'] : null,
    'video_embed'      => video_embed_info($z['video_url']),
    'stanovi'          => $stanovi,
], JSON_UNESCAPED_UNICODE);
