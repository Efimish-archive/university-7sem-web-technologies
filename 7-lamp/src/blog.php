<?php
require_once __DIR__ . '/db.php';
$limit = 5;
$page = max(1, (int) ($_GET['page'] ?? 1));
$search = trim((string) ($_GET['search'] ?? ''));
$where = $search === '' ? '' : ' WHERE title LIKE :term OR content LIKE :term';
$count = $db->prepare('SELECT COUNT(*) FROM posts' . $where);
if ($search !== '')
  $count->execute(['term' => '%' . $search . '%']);
else
  $count->execute();
$total = (int) $count->fetchColumn();
$pages = max(1, (int) ceil($total / $limit));
$page = min($page, $pages);
$sql = 'SELECT p.title, p.content, p.created_at, u.name AS author FROM posts p LEFT JOIN users u ON u.id = p.author_id' . ($search === '' ? '' : ' WHERE p.title LIKE :term OR p.content LIKE :term') . ' ORDER BY p.created_at DESC, p.id DESC LIMIT :limit OFFSET :offset';
$stmt = $db->prepare($sql);
if ($search !== '')
  $stmt->bindValue(':term', '%' . $search . '%');
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', ($page - 1) * $limit, PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll();
$pageTitle = 'Блог';
include __DIR__ . '/header.php';
?>
<h2>Блог</h2>
<form method="get"><label>Поиск по заголовку и тексту <input name="search" value="<?= e($search) ?>"
      maxlength="100"></label><button>Найти</button> <?php if ($search !== ''): ?><a
      href="/blog.php">Сбросить</a><?php endif; ?></form>
<?php if (!$posts): ?>
  <p>Записей не найдено.</p><?php endif; ?>
<?php foreach ($posts as $post): ?>
  <article>
    <h3><?= e($post['title']) ?></h3>
    <p><?= nl2br(e($post['content'])) ?></p><small><?= e($post['author'] ?? 'Автор не указан') ?> ·
      <?= e($post['created_at']) ?></small>
  </article><?php endforeach; ?>
<?php if ($pages > 1): ?>
  <nav aria-label="Страницы блога"><?php for ($i = 1; $i <= $pages; $i++): ?> <a
        href="?page=<?= $i ?>&amp;search=<?= rawurlencode($search) ?>" <?= $i === $page ? ' aria-current="page"' : '' ?>><?= $i ?></a> <?php endfor; ?></nav><?php endif; ?>
<?php include __DIR__ . '/footer.php'; ?>
