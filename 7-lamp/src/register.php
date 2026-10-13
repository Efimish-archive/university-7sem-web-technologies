<?php
require_once __DIR__ . '/db.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verifyCsrf();
  $name = trim((string) ($_POST['name'] ?? ''));
  $email = trim((string) ($_POST['email'] ?? ''));
  $password = (string) ($_POST['password'] ?? '');
  if ($name === '' || mb_strlen($name) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
    $error = 'Укажите имя, корректный email и пароль не короче 8 символов.';
  } else {
    try {
      $stmt = $db->prepare('INSERT INTO users (email, password_hash, name) VALUES (?, ?, ?)');
      $stmt->execute([$email, password_hash($password, PASSWORD_DEFAULT), $name]);
      session_regenerate_id(true);
      $_SESSION['user_id'] = (int) $db->lastInsertId();
      header('Location: /');
      exit;
    } catch (PDOException $exception) {
      $error = $exception->getCode() === '23000' ? 'Этот email уже зарегистрирован.' : 'Не удалось создать аккаунт.';
    }
  }
}
$pageTitle = 'Регистрация';
include __DIR__ . '/header.php';
?>
<h2>Регистрация</h2><?php if ($error): ?>
  <p class="error"><?= e($error) ?></p><?php endif; ?>
<form method="post"><label>Имя <input name="name" maxlength="100" required></label><label>Email <input type="email"
      name="email" required></label><label>Пароль (минимум 8 символов) <input type="password" name="password"
      minlength="8" required></label><input type="hidden" name="csrf_token"
    value="<?= e(csrfToken()) ?>"><button>Создать аккаунт</button></form>
<?php include __DIR__ . '/footer.php'; ?>
