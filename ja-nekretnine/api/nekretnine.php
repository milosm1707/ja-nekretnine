<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json; charset=utf-8');

$pdo = getDB();

$sql = "SELECT id, naziv, opis, tip, status_prodaje, kategorija, cijena, lokacija, kvadratura, sobe, istaknuta, naslovna_slika
        FROM nekretnine WHERE aktivna = 1";
$params = [];

if (!empty($_GET['tip']) && in_array($_GET['tip'], ['Prodaja', 'Iznajmljivanje'], true)) {
    $sql .= " AND tip = ?";
    $params[] = $_GET['tip'];
}

if (!empty($_GET['istaknute'])) {
    $sql .= " AND istaknuta = 1";
}

$sql .= " ORDER BY istaknuta DESC, created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$out = array_map(function ($n) {
    return [
        'id'         => (int)$n['id'],
        'naziv'      => $n['naziv'],
        'opis'       => $n['opis'],
        'tip'        => $n['tip'],
        'status_prodaje' => $n['status_prodaje'],
        'kategorija' => $n['kategorija'],
        'cijena'     => $n['cijena'],
        'lokacija'   => $n['lokacija'],
        'kvadratura' => (float)$n['kvadratura'],
        'sobe'       => (int)$n['sobe'],
        'istaknuta'  => (bool)$n['istaknuta'],
        'slika'      => $n['naslovna_slika'] ? UPLOAD_URL . $n['naslovna_slika'] : '',
    ];
}, $rows);

echo json_encode($out, JSON_UNESCAPED_UNICODE);
