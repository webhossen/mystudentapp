<?php
require_once __DIR__.'/config/config.php';

header('Content-Type: application/xml; charset=utf-8');

$pdo = getPDO();
$baseUrl = rtrim(BASE_URL, '/');

// Static pages
$staticPages = [
    '', 'about.php', 'contact.php', 'faq.php', 'product_list.php',
    'login.php', 'signup.php', 'forgot_password.php', 'cart.php'
];

$blogPosts = [];
try {
    $stmt = $pdo->query('SELECT id, slug, updated_at FROM blog_posts WHERE status=1 ORDER BY created_at DESC');
    $blogPosts = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('sitemap blog query failed: '.$e->getMessage());
}

$products = [];
try {
    $stmt = $pdo->query('SELECT id, slug, updated_at FROM products WHERE status=1 AND (quantity > 0 OR quantity IS NULL) ORDER BY id');
    $products = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('sitemap query failed: '.$e->getMessage());
}

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <?php foreach ($staticPages as $p): ?>
  <url>
    <loc><?php echo htmlspecialchars($baseUrl.'/'.ltrim($p, '/')); ?></loc>
    <changefreq>weekly</changefreq>
    <priority><?php echo $p === '' ? '1.0' : '0.8'; ?></priority>
  </url>
  <?php endforeach; ?>
  <?php foreach ($products as $p): ?>
  <url>
    <loc><?php echo htmlspecialchars($baseUrl.'/product_detail.php?id='.$p['id']); ?></loc>
    <lastmod><?php echo date('Y-m-d', strtotime($p['updated_at'] ?? 'now')); ?></lastmod>
    <changefreq>daily</changefreq>
    <priority>0.9</priority>
  </url>
  <?php endforeach; ?>
  <?php foreach ($blogPosts as $p): ?>
  <url>
    <loc><?php echo htmlspecialchars($baseUrl.'/blog_detail.php?slug='.urlencode($p['slug'] ?: $p['id'])); ?></loc>
    <lastmod><?php echo date('Y-m-d', strtotime($p['updated_at'] ?? 'now')); ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.7</priority>
  </url>
  <?php endforeach; ?>
  <url>
    <loc><?php echo htmlspecialchars($baseUrl.'/blog.php'); ?></loc>
    <changefreq>weekly</changefreq>
    <priority>0.7</priority>
  </url>
</urlset>
