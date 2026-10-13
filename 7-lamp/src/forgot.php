<?php
require_once __DIR__ . '/db.php';
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verifyCsrf();
  $email = trim((string) ($_POST['email'] ?? ''));
  if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($user = $stmt->fetch()) {
      $token = bin2hex(random_bytes(32));
      $expires = (new DateTimeImmutable('+1 hour'))->format('Y-m-d H:i:s');
      $stmt = $db->prepare('UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?');
      $stmt->execute([$token, $expires, $user['id']]);
      $autoload = __DIR__ . '/../vendor/autoload.php';
      if (is_file($autoload)) {
        require_once $autoload;
        try {
          $mail = new PHPMailer\PHPMailer\PHPMailer(true);
          $mail->isSMTP();
          $mail->Host = getenv('SMTP_HOST') ?: 'localhost';
          $mail->Port = (int) (getenv('SMTP_PORT') ?: 587);
          if (getenv('SMTP_USER')) {
            $mail->SMTPAuth = true;
            $mail->Username = getenv('SMTP_USER');
            $mail->Password = getenv('SMTP_PASSWORD') ?: '';
          }
          $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
          $from = getenv('MAIL_FROM') ?: 'noreply@example.com';
          $mail->setFrom($from);
          $mail->addAddress($email);
          $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
          $mail->Subject = 'Сброс пароля';
          $mail->Body = "Для смены пароля откройте: $scheme://" . $_SERVER['HTTP_HOST'] . '/reset.php?token=' . $token . "\nСсылка действует один час.";
          $mail->send();
          $message = 'Если такой аккаунт существует, письмо со ссылкой отправлено.';
        } catch (Throwable $exception) {
          error_log('Password reset mail failed: ' . $exception->getMessage());
          $message = 'Не удалось отправить письмо. Проверьте настройки SMTP.';
        }
      } else {
        error_log('PHPMailer is not installed; reset link token created for user ' . $user['id']);
        $message = 'Для отправки письма настройте SMTP в compose.yaml.';
      }
    } else {
      $message = 'Если такой аккаунт существует, письмо со ссылкой отправлено.';
    }
  } else
    $message = 'Введите корректный email.';
}
$pageTitle = 'Восстановление пароля';
include __DIR__ . '/header.php';
?>
<h2>Восстановление пароля</h2>

<?php if ($message): ?>
  <p class="message">
    <?= e($message) ?>
  </p>
<?php endif; ?>
<form method="post">
  <label>Email <input type="email" name="email" required></label>
  <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
  <button>Отправить ссылку</button>
</form>
<?php include __DIR__ . '/footer.php'; ?>
