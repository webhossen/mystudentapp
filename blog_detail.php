<?php
require_once __DIR__.'/config/config.php';
$pdo = getPDO();

$slug = trim($_GET['slug'] ?? '');
if (!$slug) {
  header('Location: blog.php'); exit;
}

$stmt = $pdo->prepare("SELECT * FROM blog_posts WHERE (slug=? OR id=?) AND status=1");
$stmt->execute([$slug, is_numeric($slug) ? (int)$slug : 0]);
$post = $stmt->fetch();

if (!$post) {
  $pageTitle = 'Post Not Found';
  require_once __DIR__.'/templates/header.php';
  echo '<div class="text-center py-5 my-4"><h5 class="fw-bold">Post not found</h5><p class="text-muted"><a href="blog.php">Back to Blog</a></p></div>';
  require_once __DIR__.'/templates/footer.php';
  exit;
}

$pageTitle = $post['title'];
require_once __DIR__.'/templates/header.php';
?>
<div class="blog-detail-article">
  <a href="blog.php" class="text-decoration-none text-muted small mb-3 d-inline-block"><i class="bi bi-arrow-left me-1"></i> Back to Blog</a>

  <img src="<?php echo htmlspecialchars($post['image'] ? BASE_URL.'/'.$post['image'] : BASE_URL.'/assets/img/placeholder.svg'); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" class="blog-detail-img" onerror="this.src='<?php echo BASE_URL; ?>/assets/img/placeholder.svg'">

  <div class="blog-detail-meta">
    <div class="author-avatar"><?php echo htmlspecialchars(strtoupper(substr($post['author'] ?: 'A', 0, 2))); ?></div>
    <span><strong><?php echo htmlspecialchars($post['author'] ?: 'Admin'); ?></strong></span>
    <span class="text-primary"><i class="bi bi-check-circle-fill"></i> Published</span>
    <span>&middot;</span>
    <span><i class="bi bi-calendar3 me-1"></i><?php echo date('F j, Y', strtotime($post['created_at'])); ?></span>
  </div>

  <h1><?php echo htmlspecialchars($post['title']); ?></h1>

  <div class="blog-detail-content">
    <?php echo nl2br(htmlspecialchars($post['content'])); ?>
  </div>
</div>
<?php require_once __DIR__.'/templates/footer.php'; ?>
