<?php
require_once __DIR__ . '/db.php';
$token = (string) ($_GET['token'] ?? $_POST['token'] ?? '');
$error = '';
$success = false;
$stmt = $db->prepare('SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()');
$stmt->execute([$token]);
$user = $token !== '' ? $stmt->fetch() : false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verifyCsrf();
  if (!$user)
    $error = 'Ссылка недействительна или срок её действия истёк.';
  elseif (strlen((string) ($_POST['password'] ?? '')) < 8)
    $error = 'Пароль должен содержать не менее 8 символов.';
  else {
    $stmt = $db->prepare('UPDATE users SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?');
    $stmt->execute([password_hash((string) $_POST['password'], PASSWORD_DEFAULT), $user['id']]);
    $success = true;
  }
}
$pageTitle = 'Сброс пароля';
include __DIR__ . '/header.php';
?>
<h2>Сброс пароля</h2><?php if ($success): ?>
  <p class="message">Пароль изменён. <a href="/login.php">Войти</a></p><?php elseif ($error || !$user): ?>
  <p class="error"><?= e($error ?: 'Ссылка недействительна или срок её действия истёк.') ?></p><?php else: ?>
  <form method="post"><label>Новый пароль <input type="password" name="password" minlength="8" required></label><input
      type="hidden" name="token" value="<?= e($token) ?>"><input type="hidden" name="csrf_token"
      value="<?= e(csrfToken()) ?>"><button>Сменить пароль</button></form><?php endif; ?>
<?php include __DIR__ . '/footer.php'; ?>
