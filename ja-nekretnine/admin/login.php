<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'Sesija je istekla, pokušaj ponovo.';
    } else {
        $result = attempt_login(trim($_POST['username'] ?? ''), $_POST['password'] ?? '');
        if ($result['success']) {
            redirect('dashboard.php');
        }
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="sr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Prijava — JA Nekretnine</title>
<meta name="robots" content="noindex, nofollow">
<link rel="stylesheet" href="assets/admin.css?v=4">
</head>
<body class="admin-login-body">
  <div class="login-box">
    <h1>JA Nekretnine</h1>
    <p class="login-sub">Panel za upravljanje nekretninama</p>

    <?php if ($error): ?>
      <div class="alert alert--error"><?= clean($error) ?></div>
    <?php endif; ?>

    <form method="post" autocomplete="off">
      <?= csrf_field() ?>
      <div class="field">
        <label for="username">Korisničko ime</label>
        <input type="text" id="username" name="username" required autofocus>
      </div>
      <div class="field">
        <label for="password">Lozinka</label>
        <input type="password" id="password" name="password" required>
      </div>
      <button type="submit" class="btn-primary">Prijavi se</button>
    </form>
  </div>
</body>
</html>
