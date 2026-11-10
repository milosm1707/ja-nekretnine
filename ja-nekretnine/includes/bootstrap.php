<?php
// Redosled je bitan: config.php mora da postavi session ini opcije
// PRE nego što se sesija pokrene.
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
