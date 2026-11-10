<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../includes/bootstrap.php';
require_login();

require_once __DIR__ . '/../phpmailer/PHPMailer.php';
require_once __DIR__ . '/../phpmailer/SMTP.php';
require_once __DIR__ . '/../phpmailer/Exception.php';

$pdo = getDB();
$greska_slanja = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['procitano_id']) && csrf_verify()) {
    $stmt = $pdo->prepare('UPDATE upiti_nekretnina SET procitano = 1 WHERE id = ?');
    $stmt->execute([(int)$_POST['procitano_id']]);
    redirect('upiti.php');
}

// ── slanje odgovora na upit ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['odgovor_id']) && csrf_verify()) {
    $upitId = (int)$_POST['odgovor_id'];
    $tekstOdgovora = trim($_POST['odgovor_tekst'] ?? '');

    $stmt = $pdo->prepare('SELECT * FROM upiti_nekretnina WHERE id = ?');
    $stmt->execute([$upitId]);
    $upit = $stmt->fetch();

    if (!$upit) {
        flash_set('error', 'Upit nije pronađen.');
        redirect('upiti.php');
    }

    if ($tekstOdgovora === '') {
        flash_set('error', 'Odgovor ne može biti prazan.');
        redirect('upiti.php');
    }

    $subject = 'Odgovor na Vaš upit – JA Nekretnine';

    $body = $tekstOdgovora . "\n\n---\nVaša originalna poruka:\n" . $upit['poruka'];

    $mail = new PHPMailer(true);
    $poslato = true;
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
        $mail->addAddress($upit['email'], $upit['ime']);

        $mail->Subject = $subject;
        $mail->Body = $body;

        $mail->send();
    } catch (Exception $e) {
        $poslato = false;
    }

    // odgovor se čuva bez obzira na uspeh slanja — da se ne izgubi ono što je admin otkucao
    $stmt = $pdo->prepare('UPDATE upiti_nekretnina SET odgovor = ?, odgovoreno_at = NOW(), procitano = 1 WHERE id = ?');
    $stmt->execute([$tekstOdgovora, $upitId]);

    if ($poslato) {
        flash_set('success', 'Odgovor je poslat na ' . $upit['email'] . '.');
    } else {
        flash_set('error', 'Odgovor je sačuvan, ali slanje emaila nije uspelo. Proveri email podešavanja (config/config.php) i pokušaj ponovo.');
    }

    redirect('upiti.php');
}

$upiti = $pdo->query("
    SELECT u.*, n.naziv AS nekretnina_naziv, z.naziv AS zgrada_naziv
    FROM upiti_nekretnina u
    LEFT JOIN nekretnine n ON n.id = u.nekretnina_id
    LEFT JOIN zgrade z ON z.id = u.zgrada_id
    ORDER BY u.created_at DESC
")->fetchAll();

$page_title = 'Upiti';
include __DIR__ . '/includes/header.php';
?>

<h1>Upiti sa sajta</h1>

<?php if (empty($upiti)): ?>
  <p class="empty-state">Još nema upita.</p>
<?php else: ?>
  <div class="inquiries-list">
    <?php foreach ($upiti as $u): ?>
      <div class="inquiry-card <?= $u['procitano'] ? 'is-read' : 'is-unread' ?>">
        <div class="inquiry-card__top">
          <strong><?= clean($u['ime']) ?></strong>
          <span class="muted"><?= clean($u['created_at']) ?></span>
        </div>
        <p class="inquiry-card__property">
          <?php if ($u['nekretnina_naziv']): ?>
            Nekretnina: <?= clean($u['nekretnina_naziv']) ?>
          <?php elseif ($u['zgrada_naziv']): ?>
            Zgrada: <?= clean($u['zgrada_naziv']) ?>
          <?php else: ?>
            Opšti upit
          <?php endif; ?>
        </p>
        <p><?= clean($u['email']) ?> <?= $u['telefon'] ? '· ' . clean($u['telefon']) : '' ?></p>
        <p class="inquiry-card__message"><?= nl2br(clean($u['poruka'])) ?></p>

        <?php if (!empty($u['odgovor'])): ?>
          <div class="inquiry-card__odgovor">
            <p class="inquiry-card__odgovor-label">Tvoj odgovor (poslat <?= clean($u['odgovoreno_at']) ?>):</p>
            <p><?= nl2br(clean($u['odgovor'])) ?></p>
          </div>
        <?php endif; ?>

        <div class="inquiry-card__actions">
          <?php if (!$u['procitano']): ?>
            <form method="post" style="display:inline">
              <?= csrf_field() ?>
              <input type="hidden" name="procitano_id" value="<?= (int)$u['id'] ?>">
              <button type="submit" class="btn-secondary btn-small">Označi kao pročitano</button>
            </form>
          <?php endif; ?>
          <button type="button" class="btn-secondary btn-small" onclick="document.getElementById('odgovorForma<?= (int)$u['id'] ?>').classList.toggle('open')">
            <?= !empty($u['odgovor']) ? 'Pošalji novi odgovor' : 'Odgovori' ?>
          </button>
        </div>

        <form method="post" id="odgovorForma<?= (int)$u['id'] ?>" class="inquiry-card__odgovor-forma">
          <?= csrf_field() ?>
          <input type="hidden" name="odgovor_id" value="<?= (int)$u['id'] ?>">
          <textarea name="odgovor_tekst" rows="4" placeholder="Napiši odgovor koji će stići na <?= clean($u['email']) ?>..." required></textarea>
          <button type="submit" class="btn-primary btn-small">Pošalji odgovor na email</button>
        </form>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
