<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../phpmailer/PHPMailer.php';
require_once __DIR__ . '/../phpmailer/SMTP.php';
require_once __DIR__ . '/../phpmailer/Exception.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metoda nije dozvoljena.']);
    exit;
}

function clean_input($v): string {
    return htmlspecialchars(trim((string)($v ?? '')), ENT_QUOTES, 'UTF-8');
}

$nekretnina_id = !empty($_POST['nekretnina_id']) ? (int)$_POST['nekretnina_id'] : null;
$zgrada_id = !empty($_POST['zgrada_id']) ? (int)$_POST['zgrada_id'] : null;
$ime = clean_input($_POST['ime'] ?? '');
$email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
$telefon = clean_input($_POST['telefon'] ?? '');
$poruka = clean_input($_POST['poruka'] ?? '');

if (!$ime || !$email || !$poruka) {
    echo json_encode(['success' => false, 'message' => 'Molimo popunite sva obavezna polja.']);
    exit;
}

$pdo = getDB();

$naziv_nekretnine = null;
if ($nekretnina_id) {
    $stmt = $pdo->prepare('SELECT naziv FROM nekretnine WHERE id = ?');
    $stmt->execute([$nekretnina_id]);
    $naziv_nekretnine = $stmt->fetchColumn() ?: null;
    if (!$naziv_nekretnine) {
        $nekretnina_id = null; // ne veruj proizvoljnom ID-u ako ne postoji
    }
}

$naziv_zgrade = null;
if ($zgrada_id) {
    $stmt = $pdo->prepare('SELECT naziv FROM zgrade WHERE id = ?');
    $stmt->execute([$zgrada_id]);
    $naziv_zgrade = $stmt->fetchColumn() ?: null;
    if (!$naziv_zgrade) {
        $zgrada_id = null;
    }
}

// snimi upit u bazu
$stmt = $pdo->prepare('INSERT INTO upiti_nekretnina (nekretnina_id, zgrada_id, ime, email, telefon, poruka) VALUES (?, ?, ?, ?, ?, ?)');
$stmt->execute([$nekretnina_id, $zgrada_id, $ime, $email, $telefon, $poruka]);

// pošalji email vlasniku
if ($naziv_nekretnine) {
    $subject = 'Upit za nekretninu: ' . $naziv_nekretnine;
} elseif ($naziv_zgrade) {
    $subject = 'Upit za zgradu: ' . $naziv_zgrade;
} else {
    $subject = 'Novi upit sa sajta – JA Nekretnine';
}

$body = "Ime: $ime\nEmail: $email\nTelefon: $telefon\n";
if ($naziv_nekretnine) {
    $body .= "Nekretnina: $naziv_nekretnine\n";
}
if ($naziv_zgrade) {
    $body .= "Zgrada: $naziv_zgrade\n";
}
$body .= "\nPoruka:\n$poruka\n";

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->CharSet = PHPMailer::CHARSET_UTF8;
    $mail->Encoding = PHPMailer::ENCODING_BASE64;
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USER;
    $mail->Password = SMTP_PASS;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;

    $mail->setFrom(SMTP_USER, SMTP_FROM_NAME);
    $mail->addAddress(SMTP_TO);
    $mail->addReplyTo($email, $ime);

    $mail->Subject = $subject;
    $mail->Body = $body;

    $mail->send();

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    // upit je već sačuvan u bazi čak i ako email ne uspe da se pošalje
    echo json_encode(['success' => true, 'warning' => 'Poruka je sačuvana ali email obaveštenje nije uspelo.']);
}
