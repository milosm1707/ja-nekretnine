<?php
// ============================================================
// JEDNOKRATNA SKRIPTA za kreiranje admin naloga.
//
// KORACI:
// 1. Upload ovaj fajl (i ceo public_html) na Hostinger
// 2. Popuni config/config.php sa podacima baze
// 3. Otvori u browseru: https://tvojsajt.com/setup/create-admin.php
// 4. Unesi korisničko ime i lozinku za sebe (vlasnika)
// 5. OBAVEZNO OBRIŠI OVAJ FAJL SA SERVERA POSLE (setup/create-admin.php)
//    - skripta se sama zaključava posle prvog korišćenja, ali je
//      najsigurnije da fajl fizički obrišeš.
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$done = false;
$error = '';

$pdo = getDB();
$existing = (int)$pdo->query('SELECT COUNT(*) FROM admin_users')->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($existing > 0) {
        $error = 'Admin nalog već postoji. Iz sigurnosnih razloga ova skripta odbija da napravi drugi. Obriši setup/create-admin.php sa servera.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';

        if (mb_strlen($username) < 3) {
            $error = 'Korisničko ime mora imati bar 3 karaktera.';
        } elseif (mb_strlen($password) < 10) {
            $error = 'Lozinka mora imati bar 10 karaktera (preporučujemo frazu od nekoliko reči).';
        } elseif ($password !== $password2) {
            $error = 'Lozinke se ne poklapaju.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO admin_users (username, password_hash) VALUES (?, ?)');
            $stmt->execute([$username, $hash]);
            $done = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="sr">
<head>
<meta charset="UTF-8">
<title>Kreiranje admin naloga</title>
<meta name="robots" content="noindex, nofollow">
<style>
  body { font-family: Arial, sans-serif; background:#141414; color:#ddd; display:flex; align-items:center; justify-content:center; min-height:100vh; margin:0; }
  .box { background:#1c1c1c; padding:40px; border-radius:8px; max-width:420px; width:100%; }
  h1 { font-size:20px; margin-bottom:20px; color:#fff; }
  label { display:block; margin:14px 0 6px; font-size:14px; color:#b8b8b0; }
  input { width:100%; padding:10px; border-radius:4px; border:1px solid #333; background:#0d0d0d; color:#fff; box-sizing:border-box; }
  button { margin-top:20px; width:100%; padding:12px; background:#7ab33a; border:none; border-radius:4px; color:#fff; font-weight:bold; cursor:pointer; }
  .error { background:#3a1616; border:1px solid #7a2a2a; padding:12px; border-radius:4px; margin-bottom:16px; color:#ffb3b3; font-size:14px; }
  .success { background:#16321a; border:1px solid #2a7a3a; padding:12px; border-radius:4px; margin-bottom:16px; color:#b3ffb8; font-size:14px; }
  .warn { font-size:13px; color:#b8b8b0; margin-top:20px; line-height:1.5; }
</style>
</head>
<body>
<div class="box">
  <h1>Kreiranje admin naloga</h1>

  <?php if ($done): ?>
    <div class="success">Nalog je uspešno napravljen! Sada se možeš prijaviti kroz svoj tajni admin link.</div>
    <p class="warn"><strong>Sada obriši ovaj fajl (setup/create-admin.php) sa servera</strong> — više nije potreban i predstavlja bezbednosni rizik ako ostane.</p>
  <?php elseif ($existing > 0): ?>
    <div class="error">Admin nalog već postoji. Ova skripta odbija da napravi drugi iz sigurnosnih razloga. Obriši ovaj fajl sa servera.</div>
  <?php else: ?>
    <?php if ($error): ?><div class="error"><?= clean($error) ?></div><?php endif; ?>
    <form method="post">
      <label for="username">Korisničko ime</label>
      <input type="text" id="username" name="username" required autocomplete="off">

      <label for="password">Lozinka (min. 10 karaktera)</label>
      <input type="password" id="password" name="password" required>

      <label for="password2">Ponovi lozinku</label>
      <input type="password" id="password2" name="password2" required>

      <button type="submit">Napravi nalog</button>
    </form>
    <p class="warn">Posle uspešnog kreiranja naloga, obriši ovaj fajl sa servera.</p>
  <?php endif; ?>
</div>
</body>
</html>
