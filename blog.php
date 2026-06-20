<?php
require_once __DIR__.'/config/config.php';
$pageTitle = 'Blog';
require_once __DIR__.'/templates/header.php';

$pdo = getPDO();

$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 9;
$offset = ($page - 1) * $perPage;

$countStmt = $pdo->query("SELECT COUNT(*) FROM blog_posts WHERE status=1");
$totalPosts = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($totalPosts / $perPage));

$stmt = $pdo->prepare("SELECT id, title, slug, excerpt, image, author, created_at FROM blog_posts WHERE status=1 ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute();
$posts = $stmt->fetchAll();
?>
<section class="blog-hero">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-lg-8">
        <h1><i class="bi bi-pencil-square me-2"></i>Our Blog</h1>
        <p>Latest news, tips, and updates from our team.</p>
      </div>
    </div>
  </div>
</section>

<div class="container blog-listing">
<?php if (!$posts): ?>
  <div class="text-center py-5 my-4">
    <div class="mb-3 d-inline-flex align-items-center justify-content-center rounded-circle" style="width:80px;height:80px;background:var(--surface-soft);font-size:2.2rem;color:var(--muted)"><i class="bi bi-journal-text"></i></div>
    <h5 class="fw-bold mb-2">No posts yet</h5>
    <p class="text-muted mb-3" style="max-width:400px;margin:0 auto">Check back soon for new articles and updates.</p>
  </div>
<?php else: ?>
  <div class="row g-4">
    <?php foreach ($posts as $post): ?>
      <div class="col-12 col-md-6 col-lg-4">
        <div class="blog-card">
          <a href="blog_detail.php?slug=<?php echo urlencode($post['slug'] ?: $post['id']); ?>" class="blog-card-img d-block">
            <img src="<?php echo htmlspecialchars($post['image'] ? BASE_URL.'/'.$post['image'] : BASE_URL.'/assets/img/placeholder.svg'); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" onerror="this.src='<?php echo BASE_URL; ?>/assets/img/placeholder.svg'">
            <span class="blog-date-badge"><i class="bi bi-calendar3 me-1"></i><?php echo date('M j, Y', strtotime($post['created_at'])); ?></span>
          </a>
          <div class="blog-card-body">
            <div class="blog-meta"><i class="bi bi-person"></i> <?php echo htmlspecialchars($post['author'] ?: 'Admin'); ?></div>
            <h5 class="blog-title"><a href="blog_detail.php?slug=<?php echo urlencode($post['slug'] ?: $post['id']); ?>"><?php echo htmlspecialchars($post['title']); ?></a></h5>
            <?php if ($post['excerpt']): ?>
            <p class="blog-excerpt"><?php echo htmlspecialchars($post['excerpt']); ?></p>
            <?php endif; ?>
            <a href="blog_detail.php?slug=<?php echo urlencode($post['slug'] ?: $post['id']); ?>" class="blog-read-more">Read More <i class="bi bi-arrow-right"></i></a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if ($totalPages > 1): ?>
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-5 pt-4 border-top">
    <span class="small text-muted">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
    <nav>
      <ul class="pagination pagination-sm mb-0">
        <?php if ($page > 1): ?>
          <li class="page-item"><a class="page-link" href="?page=<?php echo $page - 1; ?>"><i class="bi bi-chevron-left"></i></a></li>
        <?php endif; ?>
        <?php
        $startPage = max(1, $page - 2);
        $endPage = min($totalPages, $page + 2);
        if ($startPage > 1): ?>
          <li class="page-item"><a class="page-link" href="?page=1">1</a></li>
          <?php if ($startPage > 2): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
        <?php endif; ?>
        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
          <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>"><a class="page-link fw-semibold" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a></li>
        <?php endfor; ?>
        <?php if ($endPage < $totalPages): ?>
          <?php if ($endPage < $totalPages - 1): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
          <li class="page-item"><a class="page-link" href="?page=<?php echo $totalPages; ?>"><?php echo $totalPages; ?></a></li>
        <?php endif; ?>
        <?php if ($page < $totalPages): ?>
          <li class="page-item"><a class="page-link" href="?page=<?php echo $page + 1; ?>"><i class="bi bi-chevron-right"></i></a></li>
        <?php endif; ?>
      </ul>
    </nav>
  </div>
  <?php endif; ?>
<?php endif; ?>
</div>
<?php require_once __DIR__.'/templates/footer.php'; ?>
