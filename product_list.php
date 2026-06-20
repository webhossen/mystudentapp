<?php
require_once __DIR__.'/config/config.php';

$cat = trim($_GET['cat'] ?? '');
$search = trim($_GET['q'] ?? '');
$pageTitle = $cat ?: 'All Products';
if ($search) $pageTitle = "Search: $search";
require_once __DIR__.'/templates/header.php';

$pdo = getPDO();

$filter = trim($_GET['filter'] ?? '');
$sort = trim($_GET['sort'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 16;
$offset = ($page - 1) * $perPage;

$baseSql = 'SELECT p.id, p.title, p.price, p.sale_price, p.short_description, p.image, c.name as category, (SELECT COUNT(*) FROM product_reviews WHERE product_id=p.id AND status=1) as review_count, (SELECT COALESCE(AVG(rating),0) FROM product_reviews WHERE product_id=p.id AND status=1) as avg_rating FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.status=1';
$countSql = 'SELECT COUNT(*) FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.status=1';
$params = [];

if($search !== ''){
  $cond = ' AND (p.title LIKE ? OR p.short_description LIKE ? OR CAST(p.id AS CHAR) LIKE ?)';
  $baseSql .= $cond;
  $countSql .= $cond;
  $params[] = "%$search%";
  $params[] = "%$search%";
  $params[] = "%$search%";
}

if($filter === 'free'){
  $baseSql .= ' AND p.price = 0';
  $countSql .= ' AND p.price = 0';
} elseif($filter === 'paid'){
  $baseSql .= ' AND p.price > 0';
  $countSql .= ' AND p.price > 0';
}

if($cat !== ''){
  $baseSql .= ' AND c.name = ?';
  $countSql .= ' AND c.name = ?';
  $params[] = $cat;
}

if($sort === 'popular'){
  $baseSql .= ' ORDER BY (SELECT COUNT(*) FROM order_items oi WHERE oi.product_id = p.id) DESC, p.id DESC';
} elseif($sort === 'price_asc'){
  $baseSql .= ' ORDER BY p.price ASC';
} elseif($sort === 'price_desc'){
  $baseSql .= ' ORDER BY p.price DESC';
} else {
  $baseSql .= ' ORDER BY RAND()';
}

try{
  $countStmt = $pdo->prepare($countSql);
  $countStmt->execute($params);
  $totalProducts = $countStmt->fetchColumn();
}catch(Exception $e){
  $totalProducts = 0;
}

$totalPages = max(1, ceil($totalProducts / $perPage));

$baseSql .= ' LIMIT ' . (int)$perPage . ' OFFSET ' . (int)$offset;

try{
  $stmt = $pdo->prepare($baseSql);
  $stmt->execute($params);
  $products = $stmt->fetchAll();
}catch(Exception $e){
  $products = [];
}

$categories = $pdo->query('SELECT name, (SELECT COUNT(*) FROM products WHERE category_id=c.id AND status=1) as cnt FROM categories c ORDER BY name')->fetchAll();

$currentParams = array_filter(['q' => $search, 'filter' => $filter, 'cat' => $cat, 'sort' => $sort, 'page' => $page]);
$buildLink = fn($extra) => 'product_list.php?' . http_build_query(array_merge($currentParams, $extra));

$hasActiveFilters = $filter || $cat || $sort || $search;

$catColors = ['#eef2ff','#fef2f2','#ffedd5','#f3e8ff','#fefce8','#ecfdf5','#fce7f3','#e0f2fe','#f5f3ff','#fff7ed'];
$catIconMap = [
  'hoodies' => 'bi-handbag',
  'jacket' => 'bi-backpack',
  'long sleeves t-shirts' => 'bi-person',
  'short sleeves t-shirts' => 'bi-person',
  'sweaters' => 'bi-bag',
  'tank tops' => 'bi-handbag',
  'tops' => 'bi-handbag',
  't-shirt' => 'bi-person',
  'audio' => 'bi-music-note',
  'fonts' => 'bi-fonts',
  'graphics' => 'bi-brush',
  'templates' => 'bi-layers',
];
$catIconDefault = 'bi-tag';
?>
<div class="pb-(-12)" style="margin-top:-70px">
  <!-- Category Circles -->
  <?php if($categories): ?>
  <section class="categories-section mb-4">
    <h2 class="fw-bold text-center mb-4" style="font-size:1.35rem;letter-spacing:-0.02em">Browse by Category</h2>
    <div class="row g-3 justify-content-center">
      <div class="col-4 col-md">
        <a href="<?php echo $buildLink(['filter' => '', 'cat' => '', 'sort' => '']); ?>" class="cat-circle">
          <div class="cat-img-wrap" style="background:<?php echo !$cat ? 'var(--primary)' : 'var(--surface-soft)'; ?>;width:56px;height:56px;display:flex;align-items:center;justify-content:center">
            <i class="bi bi-grid-fill" style="font-size:1.2rem;color:<?php echo !$cat ? '#fff' : 'var(--text-secondary)'; ?>"></i>
          </div>
          <span class="cat-label">All</span>
        </a>
      </div>
      <?php $ci = 0; foreach($categories as $c):
        if((int)$c['cnt'] < 1) continue;
        $bg = $catColors[$ci % count($catColors)];
        $icon = $catIconMap[strtolower($c['name'])] ?? $catIconDefault;
      ?>
      <div class="col-4 col-md">
        <a href="<?php echo $buildLink(['cat' => $c['name'], 'filter' => '', 'sort' => '']); ?>" class="cat-circle">
          <div class="cat-img-wrap" style="background:<?php echo $cat === $c['name'] ? 'var(--primary)' : $bg; ?>;width:56px;height:56px;display:flex;align-items:center;justify-content:center">
            <i class="bi <?php echo $icon; ?>" style="font-size:1.2rem;color:<?php echo $cat === $c['name'] ? '#fff' : 'var(--text-secondary)'; ?>"></i>
          </div>
          <span class="cat-label"><?php echo htmlspecialchars($c['name']); ?></span>
        </a>
      </div>
      <?php $ci++; endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- Sort + Search Toolbar -->
  <div class="d-flex flex-wrap align-items-center gap-2 mb-3 shop-toolbar" style="margin-top:-70px">
    <div class="d-flex flex-wrap gap-1 align-items-center">
      <span class="small fw-medium text-muted me-1">Sort:</span>
      <a href="<?php echo $buildLink(['sort' => '']); ?>" class="btn btn-sm px-3 <?php echo !$sort ? 'btn-primary' : 'btn-outline-secondary'; ?>">Newest</a>
      <a href="<?php echo $buildLink(['sort' => 'popular']); ?>" class="btn btn-sm px-3 <?php echo $sort === 'popular' ? 'btn-primary' : 'btn-outline-secondary'; ?>">Popular</a>
      <a href="<?php echo $buildLink(['sort' => 'price_asc']); ?>" class="btn btn-sm px-3 <?php echo $sort === 'price_asc' ? 'btn-primary' : 'btn-outline-secondary'; ?>">Low Price</a>
      <a href="<?php echo $buildLink(['sort' => 'price_desc']); ?>" class="btn btn-sm px-3 <?php echo $sort === 'price_desc' ? 'btn-primary' : 'btn-outline-secondary'; ?>">High Price</a>
    </div>
    <form class="ms-auto d-flex" method="GET" action="product_list.php" style="max-width:220px;width:100%">
      <div class="input-group input-group-sm">
        <input class="form-control" name="q" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
        <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
        <input type="hidden" name="cat" value="<?php echo htmlspecialchars($cat); ?>">
        <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">
        <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i></button>
      </div>
    </form>
  </div>

  <?php if(!$products): ?>
  <div class="text-center py-5 my-4">
    <div class="mb-3 d-inline-flex align-items-center justify-content-center rounded-circle" style="width:80px;height:80px;background:var(--surface-soft);font-size:2.2rem;color:var(--muted)"><i class="bi bi-inbox"></i></div>
    <h5 class="fw-bold mb-2">No products found</h5>
    <p class="text-muted mb-3" style="max-width:400px;margin:0 auto"><?php echo $hasActiveFilters ? 'Try adjusting your filters or search terms.' : 'No products have been added yet.'; ?></p>
    <?php if($hasActiveFilters): ?>
      <a href="product_list.php" class="btn btn-primary rounded-pill px-4 mt-2"><i class="bi bi-x-circle me-2"></i>Clear All Filters</a>
    <?php endif; ?>
  </div>
  <?php else: ?>
  <div class="row g-3">
    <?php foreach($products as $p):
      $plHasSale = !empty($p['sale_price']) && $p['sale_price'] > 0;
      $plDiscPct = $plHasSale ? round((1 - $p['sale_price'] / $p['price']) * 100) : 0;
    ?>
      <div class="col-6 col-md-4 col-lg-3">
        <div class="product-card product-card-ts" onclick="location.href='product_detail.php?id=<?php echo $p['id']; ?>'" style="cursor:pointer">
          <div class="product-img-wrap">
            <img src="<?php echo htmlspecialchars($p['image'] ?: 'assets/img/placeholder.svg'); ?>" alt="<?php echo htmlspecialchars($p['title']); ?>" onerror="this.src='assets/img/placeholder.svg'">
            <div class="product-tags">
              <?php if($plHasSale): ?>
                <span class="tag tag-sale">-<?php echo $plDiscPct; ?>%</span>
              <?php endif; ?>
            </div>
            <?php $inWL = isset($_SESSION['wishlist'][$p['id']]); ?>
            <span class="wl-toggle btn btn-sm p-1 border-0" data-id="<?php echo $p['id']; ?>" style="background:var(--surface);border-radius:50%;width:32px;height:32px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:inherit;position:absolute;top:8px;right:8px;z-index:2;box-shadow:0 1px 3px rgba(0,0,0,0.12)" title="<?php echo $inWL ? 'Remove from Wishlist' : 'Add to Wishlist'; ?>">
              <i class="bi <?php echo $inWL ? 'bi-heart-fill text-danger' : 'bi-heart'; ?>" style="font-size:1rem;"></i>
            </span>
          </div>
          <div class="product-body">
            <div class="product-category"><?php echo htmlspecialchars($p['category'] ?: 'General'); ?></div>
            <div class="product-name"><?php echo htmlspecialchars($p['title']); ?></div>
            <div class="d-flex align-items-center gap-1 mt-1" style="min-height:1.2rem">
              <span class="text-warning" style="font-size:0.75rem;white-space:nowrap">
                <?php $ar = round($p['avg_rating']); for ($i = 1; $i <= 5; $i++): ?>
                  <i class="bi bi-star<?php echo $i <= $ar ? '-fill' : ''; ?>"></i>
                <?php endfor; ?>
              </span>
              <small class="text-muted">(<?php echo $p['review_count']; ?>)</small>
            </div>
            <?php if(!empty($p['short_description'])): ?>
            <div class="product-desc text-muted small mt-1" style="display:-webkit-box;-webkit-line-clamp:2;line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;min-height:2.4em"><?php echo htmlspecialchars($p['short_description']); ?></div>
            <?php endif; ?>
            <div class="d-flex justify-content-between align-items-center mt-2 pt-2" style="border-top:1px solid var(--border)">
              <div class="product-price">
                <?php echo formatPrice($plHasSale ? $p['sale_price'] : $p['price']); ?>
                <?php if($plHasSale): ?><span class="old-price"><?php echo formatPrice($p['price']); ?></span><?php endif; ?>
              </div>
              <span class="btn btn-sm btn-primary btn-add-cart" data-id="<?php echo $p['id']; ?>" style="cursor:pointer;border-radius:50rem"><i class="bi bi-cart-plus"></i></span>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Pagination -->
  <?php if($totalPages > 1): ?>
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-5 pt-4 border-top">
    <span class="small text-muted">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
    <nav>
      <ul class="pagination pagination-sm mb-0">
        <?php if($page > 1): ?>
          <li class="page-item"><a class="page-link" href="<?php echo $buildLink(['page' => $page - 1]); ?>"><i class="bi bi-chevron-left"></i></a></li>
        <?php endif; ?>
        <?php
        $startPage = max(1, $page - 2);
        $endPage = min($totalPages, $page + 2);
        if($startPage > 1): ?>
          <li class="page-item"><a class="page-link" href="<?php echo $buildLink(['page' => 1]); ?>">1</a></li>
          <?php if($startPage > 2): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
        <?php endif; ?>
        <?php for($i = $startPage; $i <= $endPage; $i++): ?>
          <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>"><a class="page-link fw-semibold" href="<?php echo $buildLink(['page' => $i]); ?>"><?php echo $i; ?></a></li>
        <?php endfor; ?>
        <?php if($endPage < $totalPages): ?>
          <?php if($endPage < $totalPages - 1): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
          <li class="page-item"><a class="page-link" href="<?php echo $buildLink(['page' => $totalPages]); ?>"><?php echo $totalPages; ?></a></li>
        <?php endif; ?>
        <?php if($page < $totalPages): ?>
          <li class="page-item"><a class="page-link" href="<?php echo $buildLink(['page' => $page + 1]); ?>"><i class="bi bi-chevron-right"></i></a></li>
        <?php endif; ?>
      </ul>
    </nav>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>
<script>
document.querySelectorAll('.wl-toggle').forEach(function(el){
  el.addEventListener('click', function(e){
    e.preventDefault();
    e.stopPropagation();
    var id = this.dataset.id;
    var icon = this.querySelector('i');
    var isFilled = icon.classList.contains('bi-heart-fill');
    fetch('cart.php?' + (isFilled ? 'remove_wishlist' : 'add_wishlist') + '=' + id + '&ajax=1').then(function(r){ return r.json(); }).then(function(d){
      if(d.success){
        icon.className = 'bi ' + (isFilled ? 'bi-heart' : 'bi-heart-fill text-danger');
        el.title = isFilled ? 'Add to Wishlist' : 'Remove from Wishlist';
        var badge = document.querySelector('.header-icons a[title="Wishlist"] .badge-count');
        if(badge) badge.textContent = d.wishlistCount;
      }
    });
  });
});
document.querySelectorAll('.btn-add-cart').forEach(function(el){
  el.addEventListener('click', function(e){
    e.preventDefault();
    e.stopPropagation();
    var btn = this;
    fetch('cart.php?add=' + this.dataset.id + '&ajax=1').then(function(r){ return r.json(); }).then(function(d){
      if(d.success){
        var badge = document.querySelector('.header-icons a[title="Cart"] .badge-count');
        if(badge) badge.textContent = d.cartCount;
        btn.innerHTML = '<i class="bi bi-check"></i>';
        setTimeout(function(){ btn.innerHTML = '<i class="bi bi-cart-plus"></i>'; }, 1500);
      }
    });
  });
});
</script>
<?php require_once __DIR__.'/templates/footer.php'; ?>
