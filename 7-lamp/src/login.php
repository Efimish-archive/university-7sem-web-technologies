<?php
require_once __DIR__ . '/db.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verifyCsrf();
  $email = trim((string) ($_POST['email'] ?? ''));
  $stmt = $db->prepare('SELECT id, password_hash FROM users WHERE email = ?');
  $stmt->execute([$email]);
  $user = $stmt->fetch();
  if ($user && password_verify((string) ($_POST['password'] ?? ''), $user['password_hash'])) {
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    if (!empty($_POST['remember'])) {
      setcookie('user_email', $email, ['expires' => time() + 60 * 60 * 24 * 30, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS'])]);
    }
    header('Location: /');
    exit;
  }
  $error = 'Неверный email или пароль.';
}
$pageTitle = 'Вход';
include __DIR__ . '/header.php';
?>

<h2>Вход</h2>

<?php if ($error): ?>
  <p class="error">
    <?= e($error) ?>
  </p>
<?php endif; ?>

<form method="post">
  <label>Email <input type="email" name="email" value="<?= e($_COOKIE['user_email'] ?? '') ?>" autocomplete="email"
      required></label>
  <label>Пароль <input type="password" name="password" autocomplete="current-password" required></label>
  <label><input type="checkbox" name="remember" value="1">
    Запомнить email на этом устройстве</label>
  <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
  <button>Войти</button>
</form>
<p><a href="/forgot.php">Забыли пароль?</a></p>
<?php include __DIR__ . '/footer.php'; ?>
