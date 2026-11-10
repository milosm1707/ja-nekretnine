<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';
require_once __DIR__ . '/phpmailer/Exception.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Metoda nije dozvoljena.'
    ]);
    exit;
}

function clean($v) {
    return htmlspecialchars(trim((string)$v), ENT_QUOTES, 'UTF-8');
}

$name = clean($_POST['name'] ?? '');
$email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
$phone = clean($_POST['phone'] ?? '');
$message = clean($_POST['message'] ?? '');

if (!$name || !$email || !$message) {
    echo json_encode([
        'success' => false,
        'message' => 'Molimo popunite sva obavezna polja.'
    ]);
    exit;
}

/* ====== SNIMI U BAZU (da se vidi u admin panelu -> Upiti) ====== */

try {
    $pdo = getDB();
    $stmt = $pdo->prepare('INSERT INTO upiti_nekretnina (nekretnina_id, zgrada_id, ime, email, telefon, poruka) VALUES (NULL, NULL, ?, ?, ?, ?)');
    $stmt->execute([$name, $email, $phone, $message]);
    $sacuvano = true;
} catch (Throwable $e) {
    error_log('contact.php: upis u bazu nije uspeo: ' . $e->getMessage());
    $sacuvano = false;
}

/* ====== EMAIL CONTENT ====== */

$subject = "Nova poruka sa sajta – JA Nekretnine";

$body = "
Ime: $name
Email: $email
Telefon: $phone

Poruka:
$message
";

/* ====== PHPMailer ====== */

$mail = new PHPMailer(true);

try {

    $mail->isSMTP();
    $mail->CharSet = PHPMailer::CHARSET_UTF8;
    $mail->Encoding = PHPMailer::ENCODING_BASE64;
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USER;
    $mail->Password   = SMTP_PASS;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;

    $mail->setFrom(SMTP_USER, SMTP_FROM_NAME);
    $mail->addAddress(SMTP_TO);

    $mail->addReplyTo($email, $name);

    $mail->Subject = $subject;
    $mail->Body    = $body;

    $mail->send();

    echo json_encode([
        'success' => true
    ]);

} catch (Exception $e) {

    // ako je upit snimljen u bazu, za posetioca je to uspeh (videćeš ga u admin panelu)
    echo json_encode([
        'success' => $sacuvano,
        'message' => 'Greška pri slanju poruke.'
    ]);

}
