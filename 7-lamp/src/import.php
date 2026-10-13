<?php
require_once __DIR__ . '/db.php';
requireLogin();
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verifyCsrf();
  $file = $_FILES['xml'] ?? null;
  if (!$file || $file['error'] !== UPLOAD_ERR_OK)
    $error = 'Не удалось загрузить файл.';
  elseif ($file['size'] > 2 * 1024 * 1024 || !is_uploaded_file($file['tmp_name']))
    $error = 'Файл превышает 2 МБ или не прошёл проверку.';
  elseif (strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION)) !== 'xml')
    $error = 'Загрузите файл с расширением .xml.';
  else {
    libxml_use_internal_errors(true);
    $xml = simplexml_load_file($file['tmp_name'], SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOBLANKS);
    if (!$xml || !in_array($xml->getName(), ['posts', 'data'], true)) {
      $error = 'Неверный XML. Корневой элемент должен быть <posts>.';
    } else {
      $posts = $xml->getName() === 'posts' ? $xml->post : $xml->posts->post;
      if (!$posts || count($posts) === 0)
        $error = 'XML не содержит элементов <post>.';
      else {
        $added = 0;
        $updated = 0;
        $skipped = 0;
        try {
          $db->beginTransaction();
          foreach ($posts as $post) {
            $title = trim((string) $post->title);
            $content = trim((string) $post->content);
            if ($title === '' || $content === '' || mb_strlen($title) > 255) {
              $skipped++;
              continue;
            }
            $check = $db->prepare('SELECT id FROM posts WHERE title = ?');
            $check->execute([$title]);
            if ($existing = $check->fetch()) {
              $stmt = $db->prepare('UPDATE posts SET content = ? WHERE id = ?');
              $stmt->execute([$content, $existing['id']]);
              $updated++;
            } else {
              $stmt = $db->prepare('INSERT INTO posts (title, content) VALUES (?, ?)');
              $stmt->execute([$title, $content]);
              $added++;
            }
          }
          $db->commit();
          $success = "Импорт завершён: добавлено $added, обновлено $updated, пропущено $skipped.";
        } catch (Throwable $exception) {
          if ($db->inTransaction())
            $db->rollBack();
          error_log('XML import failed: ' . $exception->getMessage());
          $error = 'Не удалось импортировать данные.';
        }
      }
    }
    libxml_clear_errors();
  }
}
$pageTitle = 'Импорт XML';
include __DIR__ . '/header.php';
?>
<h2>Импорт записей из XML</h2><?php if ($error): ?>
  <p class="error"><?= e($error) ?></p><?php endif; ?><?php if ($success): ?>
  <p class="message"><?= e($success) ?></p><?php endif; ?>
<p>Файл до 2 МБ. Формат:</p>
<pre><code>&lt;posts&gt;
  &lt;post&gt;&lt;title&gt;Заголовок&lt;/title&gt;&lt;content&gt;Текст записи&lt;/content&gt;&lt;/post&gt;
&lt;/posts&gt;</code></pre>
<form method="post" enctype="multipart/form-data"><label>XML-файл <input type="file" name="xml"
      accept=".xml,text/xml,application/xml" required></label><input type="hidden" name="csrf_token"
    value="<?= e(csrfToken()) ?>"><button>Импортировать</button></form>
<?php include __DIR__ . '/footer.php'; ?>
