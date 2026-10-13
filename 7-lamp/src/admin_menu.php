<?php
require_once __DIR__ . '/db.php';
requireLogin();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verifyCsrf();
  $action = (string) ($_POST['action'] ?? '');
  if ($action === 'save') {
    $id = (int) ($_POST['id'] ?? 0);
    $title = trim((string) ($_POST['title'] ?? ''));
    $url = trim((string) ($_POST['url'] ?? ''));
    $safeUrl = (str_starts_with($url, '/') && !str_starts_with($url, '//')) || preg_match('#^https?://#i', $url);
    if ($title === '' || mb_strlen($title) > 100 || $url === '' || mb_strlen($url) > 255 || preg_match('/[\r\n]/', $url) || !$safeUrl)
      $error = 'Укажите название и URL вида /blog.php или https://example.com.';
    elseif ($id > 0) {
      $stmt = $db->prepare('UPDATE menu_items SET title = ?, url = ? WHERE id = ?');
      $stmt->execute([$title, $url, $id]);
    } else {
      $stmt = $db->prepare('INSERT INTO menu_items (title, url, order_num) VALUES (?, ?, (SELECT max_order FROM (SELECT COALESCE(MAX(order_num), 0) + 1 AS max_order FROM menu_items) AS next_order))');
      $stmt->execute([$title, $url]);
    }
  } elseif ($action === 'delete') {
    $stmt = $db->prepare('DELETE FROM menu_items WHERE id = ?');
    $stmt->execute([(int) ($_POST['id'] ?? 0)]);
  } elseif ($action === 'publish') {
    $stmt = $db->prepare('UPDATE menu_items SET is_published = ? WHERE id = ?');
    $stmt->execute([isset($_POST['published']) ? 1 : 0, (int) ($_POST['id'] ?? 0)]);
  } elseif ($action === 'order') {
    foreach (($_POST['order'] ?? []) as $id => $order) {
      $stmt = $db->prepare('UPDATE menu_items SET order_num = ? WHERE id = ?');
      $stmt->execute([max(0, (int) $order), (int) $id]);
    }
  }
  if ($error === '') {
    header('Location: /admin_menu.php');
    exit;
  }
}
$items = $db->query('SELECT * FROM menu_items ORDER BY order_num, id')->fetchAll();
$pageTitle = 'Управление меню';
include __DIR__ . '/header.php';
?>
<h2>Управление меню</h2><?php if ($error): ?>
  <p class="error"><?= e($error) ?></p><?php endif; ?>
<h3>Добавить пункт</h3>
<form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden"
    name="action" value="save"><label>Название <input name="title" maxlength="100" required></label><label>URL <input
      name="url" maxlength="255" placeholder="/blog.php" required></label><button>Добавить</button></form>
<h3>Пункты меню</h3>
<form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden"
    name="action" value="order">
  <table>
    <thead>
      <tr>
        <th>Порядок</th>
        <th>Название</th>
        <th>URL</th>
        <th>Публикация</th>
      </tr>
    </thead>
    <tbody><?php foreach ($items as $item): ?>
        <tr>
          <td><input type="number" min="0" name="order[<?= (int) $item['id'] ?>]" value="<?= (int) $item['order_num'] ?>">
          </td>
          <td><?= e($item['title']) ?></td>
          <td><?= e($item['url']) ?></td>
          <td><?= $item['is_published'] ? 'Опубликован' : 'Скрыт' ?></td>
        </tr><?php endforeach; ?>
    </tbody>
  </table><button>Сохранить порядок</button>
</form>
<?php foreach ($items as $item): ?>
  <article>
    <form method="post" class="card"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input
        type="hidden" name="action" value="save"><input type="hidden" name="id"
        value="<?= (int) $item['id'] ?>"><label>Название <input name="title" maxlength="100"
          value="<?= e($item['title']) ?>" required></label><label>URL <input name="url" maxlength="255"
          value="<?= e($item['url']) ?>" required></label><button>Сохранить изменения</button></form>
    <form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden"
        name="action" value="publish"><input type="hidden" name="id" value="<?= (int) $item['id'] ?>"><label><input
          type="checkbox" name="published" value="1" <?= $item['is_published'] ? 'checked' : '' ?>> Показывать в
        меню</label><button>Изменить публикацию</button></form>
    <form method="post" onsubmit="return confirm('Удалить пункт меню?')"><input type="hidden" name="csrf_token"
        value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id"
        value="<?= (int) $item['id'] ?>"><button>Удалить</button></form>
  </article><?php endforeach; ?>
<?php include __DIR__ . '/footer.php'; ?>
