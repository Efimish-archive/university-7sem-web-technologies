<?php
require_once __DIR__ . '/db.php';
requireLogin();
$userId = (int) $_SESSION['user_id'];
$error = '';
$uploadDir = __DIR__ . '/uploads';
if (!is_dir($uploadDir))
  mkdir($uploadDir, 0775, true);
function saveImageResource($image, string $path, string $mime): bool
{
  return match ($mime) { 'image/png' => imagepng($image, $path), 'image/webp' => imagewebp($image, $path, 85), default => imagejpeg($image, $path, 88)};
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verifyCsrf();
  if (isset($_POST['delete_id'])) {
    $stmt = $db->prepare('SELECT original_path, thumb_path FROM images WHERE id = ? AND user_id = ?');
    $stmt->execute([(int) $_POST['delete_id'], $userId]);
    $image = $stmt->fetch();
    if ($image) {
      foreach (['original_path', 'thumb_path'] as $key) {
        $path = __DIR__ . '/' . $image[$key];
        if (is_file($path) && str_starts_with(realpath($path) ?: '', realpath($uploadDir) . DIRECTORY_SEPARATOR))
          unlink($path);
      }
      $stmt = $db->prepare('DELETE FROM images WHERE id = ? AND user_id = ?');
      $stmt->execute([(int) $_POST['delete_id'], $userId]);
    }
    header('Location: /gallery.php');
    exit;
  }
  $file = $_FILES['image'] ?? null;
  if (!$file || $file['error'] !== UPLOAD_ERR_OK)
    $error = 'Выберите изображение размером до 5 МБ.';
  elseif ($file['size'] > 5 * 1024 * 1024 || !is_uploaded_file($file['tmp_name']))
    $error = 'Файл слишком большой или не прошёл проверку.';
  else {
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $info = @getimagesize($file['tmp_name']);
    if (!isset($extensions[$mime]) || !$info || $info[0] > 8000 || $info[1] > 8000)
      $error = 'Поддерживаются JPEG, PNG и WebP не более 8000×8000.';
    else {
      $name = bin2hex(random_bytes(16));
      $original = "$uploadDir/$name.{$extensions[$mime]}";
      $thumb = "$uploadDir/$name-thumb.{$extensions[$mime]}";
      $source = @imagecreatefromstring(file_get_contents($file['tmp_name']));
      if (!$source)
        $error = 'Не удалось обработать изображение.';
      else {
        $originalWidth = imagesx($source);
        $originalHeight = imagesy($source);
        $watermark = 'EFIM 2026';
        $watermarkColor = imagecolorallocatealpha($source, 255, 255, 255, 20);
        imagestring($source, 5, max(4, $originalWidth - imagefontwidth(5) * strlen($watermark) - 12), max(4, $originalHeight - imagefontheight(5) - 12), $watermark, $watermarkColor);
        if (!saveImageResource($source, $original, $mime)) {
          imagedestroy($source);
          $error = 'Не удалось сохранить изображение.';
        } else {
          $width = imagesx($source);
          $height = imagesy($source);
          $scale = min(1, 480 / max($width, $height));
          $tw = max(1, (int) round($width * $scale));
          $th = max(1, (int) round($height * $scale));
          $preview = imagecreatetruecolor($tw, $th);
          if ($mime === 'image/png' || $mime === 'image/webp') {
            imagealphablending($preview, false);
            imagesavealpha($preview, true);
            imagefill($preview, 0, 0, imagecolorallocatealpha($preview, 0, 0, 0, 127));
          }
          imagecopyresampled($preview, $source, 0, 0, 0, 0, $tw, $th, $width, $height);
          $white = imagecolorallocatealpha($preview, 255, 255, 255, 20);
          $shadow = imagecolorallocatealpha($preview, 0, 0, 0, 35);
          $label = 'EFIM 2026';
          imagestring($preview, 4, max(4, $tw - imagefontwidth(4) * strlen($label) - 9), max(4, $th - imagefontheight(4) - 8), $label, $shadow);
          imagestring($preview, 4, max(3, $tw - imagefontwidth(4) * strlen($label) - 10), max(3, $th - imagefontheight(4) - 9), $label, $white);
          $ok = saveImageResource($preview, $thumb, $mime);
          imagedestroy($preview);
          imagedestroy($source);
          if (!$ok) {
            @unlink($original);
            $error = 'Не удалось создать миниатюру.';
          } else {
            $stmt = $db->prepare('INSERT INTO images (filename, original_path, thumb_path, user_id) VALUES (?, ?, ?, ?)');
            $stmt->execute([mb_substr(basename((string) $file['name']), 0, 255), 'uploads/' . basename($original), 'uploads/' . basename($thumb), $userId]);
            header('Location: /gallery.php');
            exit;
          }
        }
      }
    }
  }
}
$stmt = $db->prepare('SELECT id, filename, original_path, thumb_path, uploaded_at FROM images WHERE user_id = ? ORDER BY uploaded_at DESC');
$stmt->execute([$userId]);
$images = $stmt->fetchAll();
$pageTitle = 'Фотогалерея';
include __DIR__ . '/header.php';
?>
<h2>Моя фотогалерея</h2><?php if ($error): ?>
  <p class="error"><?= e($error) ?></p><?php endif; ?>
<form method="post" enctype="multipart/form-data"><label>Изображение (JPEG, PNG или WebP, до 5 МБ) <input type="file"
      name="image" accept="image/jpeg,image/png,image/webp" required></label><input type="hidden" name="csrf_token"
    value="<?= e(csrfToken()) ?>"><button>Загрузить</button></form>
<div class="grid"><?php foreach ($images as $image): ?>
    <article><a href="/<?= e($image['original_path']) ?>"><img class="thumb" src="/<?= e($image['thumb_path']) ?>"
          alt="<?= e($image['filename']) ?>"></a>
      <p><?= e($image['filename']) ?></p><small><?= e($image['uploaded_at']) ?></small>
      <form method="post" onsubmit="return confirm('Удалить изображение?')"><input type="hidden" name="csrf_token"
          value="<?= e(csrfToken()) ?>"><input type="hidden" name="delete_id"
          value="<?= (int) $image['id'] ?>"><button>Удалить</button></form>
    </article><?php endforeach; ?>
</div>
<?php if (!$images): ?>
  <p>Вы пока не загрузили изображения.</p><?php endif; ?>
<?php include __DIR__ . '/footer.php'; ?>
