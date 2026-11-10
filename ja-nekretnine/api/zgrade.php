<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json; charset=utf-8');

$pdo = getDB();

$sql = "SELECT id, naziv, opis, lokacija, adresa, godina_izgradnje, broj_spratova, istaknuta, naslovna_slika,
        (SELECT COUNT(*) FROM nekretnine n WHERE n.zgrada_id = zgrade.id AND n.aktivna = 1) AS broj_stanova
        FROM zgrade WHERE aktivna = 1";
$params = [];

if (!empty($_GET['istaknute'])) {
    $sql .= " AND istaknuta = 1";
}

$sql .= " ORDER BY istaknuta DESC, created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$out = array_map(function ($z) {
    return [
        'id'               => (int)$z['id'],
        'naziv'            => $z['naziv'],
        'opis'             => $z['opis'],
        'lokacija'         => $z['lokacija'],
        'adresa'           => $z['adresa'],
        'godina_izgradnje' => $z['godina_izgradnje'] ? (int)$z['godina_izgradnje'] : null,
        'broj_spratova'    => $z['broj_spratova'] ? (int)$z['broj_spratova'] : null,
        'istaknuta'        => (bool)$z['istaknuta'],
        'broj_stanova'     => (int)$z['broj_stanova'],
        'slika'            => $z['naslovna_slika'] ? UPLOAD_ZGRADE_URL . $z['naslovna_slika'] : '',
    ];
}, $rows);

echo json_encode($out, JSON_UNESCAPED_UNICODE);
