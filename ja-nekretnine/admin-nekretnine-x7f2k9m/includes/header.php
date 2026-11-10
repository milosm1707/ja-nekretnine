<?php
// Očekuje da je bootstrap.php već učitan i require_login() već pozvan
// u fajlu koji uključuje ovaj header.
$flash_success = flash_get('success');
$flash_error = flash_get('error');
?>
<!DOCTYPE html>
<html lang="sr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($page_title) ? clean($page_title) . ' — ' : '' ?>Admin panel</title>
<meta name="robots" content="noindex, nofollow">
<link rel="stylesheet" href="assets/admin.css?v=4">
</head>
<body class="admin-body">
<header class="admin-header">
  <div class="admin-header__brand">JA Nekretnine <span>· panel</span></div>
  <nav class="admin-header__nav">
    <a href="dashboard.php">Nekretnine</a>
    <a href="zgrade-dashboard.php">Zgrade</a>
    <a href="upiti.php">Upiti</a>
    <a href="logout.php" class="logout-link">Odjava (<?= clean($_SESSION['admin_username'] ?? '') ?>)</a>
  </nav>
</header>
<main class="admin-main">
  <?php if ($flash_success): ?>
    <div class="alert alert--success"><?= clean($flash_success) ?></div>
  <?php endif; ?>
  <?php if ($flash_error): ?>
    <div class="alert alert--error"><?= clean($flash_error) ?></div>
  <?php endif; ?>
