<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

$db = new PDO(
  sprintf(
    'mysql:host=%s;dbname=%s;charset=utf8mb4',
    getenv('DB_HOST') ?: 'db',
    getenv('DB_NAME') ?: 'database'
  ),
  getenv('DB_USER') ?: 'user',
  getenv('DB_PASSWORD') ?: 'user_password',
  [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

function e(?string $value): string
{
  return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function isLoggedIn(): bool
{
  return isset($_SESSION['user_id']);
}

function requireLogin(): void
{
  if (!isLoggedIn()) {
    header('Location: /login.php');
    exit;
  }
}

function csrfToken(): string
{
  if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf_token'];
}

function verifyCsrf(): void
{
  if (!hash_equals(csrfToken(), (string) ($_POST['csrf_token'] ?? ''))) {
    http_response_code(403);
    exit('Недействительный запрос. Обновите страницу и попробуйте ещё раз.');
  }
}

function getMenu(): string
{
  global $db;
  $items = $db->query('SELECT title, url FROM menu_items WHERE is_published = 1 ORDER BY order_num, id')->fetchAll();
  $html = '<ul>';
  foreach ($items as $item) {
    $html .= '<li><a href="' . e($item['url']) . '">' . e($item['title']) . '</a></li>';
  }
  if (isLoggedIn()) {
    $html .= '<li><a href="/gallery.php">Мои изображения</a></li><li><a href="/admin_menu.php">Управление меню</a></li><li><a href="/import.php">Импорт XML</a></li><li><a href="/logout.php">Выход</a></li>';
  } else {
    $html .= '<li><a href="/register.php">Регистрация</a></li><li><a href="/login.php">Вход</a></li>';
  }
  return $html . '</ul>';
}
