<?php
// ============================================================
// KONFIGURACIJA - kopiraj ovaj fajl u config.php i popuni svojim podacima.
// config.php se NE commituje (nalazi se u .gitignore).
// ============================================================

// --- BAZA PODATAKA (Hostinger hPanel -> Databases -> MySQL Databases) ---
define('DB_HOST', 'localhost');                 // gotovo uvek 'localhost' na Hostingeru
define('DB_NAME', 'ime_baze');       // npr. u123456789_nekretnine
define('DB_USER', 'korisnik_baze'); // npr. u123456789_admin
define('DB_PASS', 'PROMENI_ME');

// --- EMAIL (SMTP - isti podaci koji već koristi contact.php) ---
define('SMTP_HOST', 'smtp.hostinger.com');
define('SMTP_USER', 'info@example.com');
define('SMTP_PASS', 'PROMENI_ME');
define('SMTP_FROM_NAME', 'JA Nekretnine');
define('SMTP_TO', 'info@example.com');     // gde stižu upiti za nekretnine

// --- SIGURNOST PRIJAVE ---
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_MINUTES', 15);

// --- UPLOAD SLIKA ---
define('UPLOAD_DIR', __DIR__ . '/../uploads/nekretnine/');
define('UPLOAD_URL', 'uploads/nekretnine/');
define('MAX_UPLOAD_SIZE', 20 * 1024 * 1024); // 20 MB po slici PRE kompresije (server je automatski smanji posle)
define('MAX_IMAGE_DIMENSION', 1920); // slika se smanjuje na max ovu širinu/visinu, kvalitet ostaje isti na ekranu
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp']);

// --- UPLOAD VIDEA (MP4 u galeriji) ---
define('MAX_VIDEO_SIZE', 60 * 1024 * 1024); // 60 MB po video fajlu
// Različiti telefoni/programi snimaju MP4 sa različitim "brand" oznakama unutar
// fajla, pa ih PHP prepozna pod različitim MIME tipovima iako su svi validni MP4.
define('ALLOWED_VIDEO_TYPES', ['video/mp4', 'video/quicktime', 'video/x-m4v', 'application/mp4']);

// --- UPLOAD DOKUMENATA (zgrade) ---
define('UPLOAD_DOC_DIR', __DIR__ . '/../uploads/dokumenti/');
define('UPLOAD_DOC_URL', 'uploads/dokumenti/');

// --- UPLOAD SLIKA ZGRADA ---
define('UPLOAD_ZGRADE_DIR', __DIR__ . '/../uploads/zgrade/');
define('UPLOAD_ZGRADE_URL', 'uploads/zgrade/');

// --- SESIJA (mora biti postavljeno pre session_start()) ---
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', '1');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    ini_set('session.cookie_secure', '1');
}

// --- PRIKAZ GREŠAKA (isključi na produkciji kad sve proveriš) ---
error_reporting(E_ALL);
ini_set('display_errors', '0'); // ne prikazuj greške posetiocima
ini_set('log_errors', '1');
