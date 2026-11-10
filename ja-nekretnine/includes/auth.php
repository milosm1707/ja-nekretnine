<?php

function is_logged_in(): bool {
    return !empty($_SESSION['admin_id']);
}

function require_login(): void {
    if (!is_logged_in()) {
        redirect('login.php');
    }
}

/**
 * Pokušava prijavu admina uz zaštitu od brute-force napada
 * (zaključavanje naloga posle MAX_LOGIN_ATTEMPTS pogrešnih pokušaja).
 */
function attempt_login(string $username, string $password): array {
    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT * FROM admin_users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user) {
        usleep(300000); // sprečava razlikovanje "ne postoji" od "pogrešna lozinka" po vremenu
        return ['success' => false, 'message' => 'Pogrešno korisničko ime ili lozinka.'];
    }

    if (!empty($user['locked_until']) && strtotime($user['locked_until']) > time()) {
        $preostalo = (int)ceil((strtotime($user['locked_until']) - time()) / 60);
        return ['success' => false, 'message' => "Nalog je privremeno zaključan zbog previše pokušaja. Pokušajte ponovo za približno {$preostalo} min."];
    }

    if (!password_verify($password, $user['password_hash'])) {
        $attempts = (int)$user['failed_attempts'] + 1;
        $lockUntil = null;
        if ($attempts >= MAX_LOGIN_ATTEMPTS) {
            $lockUntil = date('Y-m-d H:i:s', time() + LOCKOUT_MINUTES * 60);
        }
        $upd = $pdo->prepare('UPDATE admin_users SET failed_attempts = ?, locked_until = ? WHERE id = ?');
        $upd->execute([$attempts, $lockUntil, $user['id']]);
        return ['success' => false, 'message' => 'Pogrešno korisničko ime ili lozinka.'];
    }

    // uspešna prijava - resetuj brojač i regeneriši ID sesije (sprečava session fixation)
    $upd = $pdo->prepare('UPDATE admin_users SET failed_attempts = 0, locked_until = NULL WHERE id = ?');
    $upd->execute([$user['id']]);

    session_regenerate_id(true);
    $_SESSION['admin_id'] = $user['id'];
    $_SESSION['admin_username'] = $user['username'];

    return ['success' => true];
}
