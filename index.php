<?php
require_once __DIR__.'/config/config.php';
require_once __DIR__.'/templates/header.php';

$pdo = getPDO();

// New Arrivals - latest 4 products
$newArrivals = [];
try{
  $stmt = $pdo->query('SELECT p.id,p.title,p.short_description,p.price,p.sale_price,p.image,c.name as category FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.status=1 ORDER BY p.id DESC LIMIT 4');
  $newArrivals = $stmt->fetchAll();
}catch(Exception $e){
  $newArrivals = [];
}

// Best Sellers - top 4 products by order quantity
$bestSellers = [];
try{
  $stmt = $pdo->query('SELECT p.id,p.title,p.short_description,p.price,p.sale_price,p.image,c.name as category,COALESCE(SUM(oi.quantity),0) AS sold FROM products p LEFT JOIN categories c ON p.category_id=c.id LEFT JOIN order_items oi ON p.id=oi.product_id WHERE p.status=1 GROUP BY p.id ORDER BY sold DESC LIMIT 4');
  $bestSellers = $stmt->fetchAll();
}catch(Exception $e){
  $bestSellers = [];
}

// Sale - products with a sale_price set, limit 4
$saleProducts = [];
try{
  $stmt = $pdo->query('SELECT p.id,p.title,p.short_description,p.price,p.sale_price,p.image,c.name as category FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.status=1 AND p.sale_price IS NOT NULL AND p.sale_price > 0 ORDER BY RAND() LIMIT 4');
  $saleProducts = $stmt->fetchAll();
}catch(Exception $e){
  $saleProducts = [];
}

// Fallback: if any tab has fewer than 4, supplement from general products
$allProducts = [];
try{
  $stmt = $pdo->query('SELECT p.id,p.title,p.short_description,p.price,p.sale_price,p.image,c.name as category FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.status=1 ORDER BY p.id DESC LIMIT 8');
  $allProducts = $stmt->fetchAll();
}catch(Exception $e){
  $allProducts = [];
}

function supplementProducts(&$tabProducts, $allProducts, $count = 4){
  if(count($tabProducts) >= $count) return;
  $ids = array_column($tabProducts, 'id');
  foreach($allProducts as $p){
    if(count($tabProducts) >= $count) break;
    if(!in_array($p['id'], $ids)){
      $tabProducts[] = $p;
    }
  }
}
supplementProducts($newArrivals, $allProducts);
supplementProducts($bestSellers, $allProducts);
supplementProducts($saleProducts, $allProducts);

// Featured products (under -16%, excluding ones already shown in tabs)
$featuredProducts = [];
try{
  $stmt = $pdo->query('SELECT p.id,p.title,p.short_description,p.price,p.sale_price,p.image,c.name as category FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.status=1 AND CAST(COALESCE(p.sale_price, p.price) AS DECIMAL(10,2)) < 39 ORDER BY p.id DESC LIMIT 4');
  $featuredProducts = $stmt->fetchAll();
}catch(Exception $e){
  $featuredProducts = [];
}
if(count($featuredProducts) < 4){
  $ids = array_column($featuredProducts, 'id');
  foreach($allProducts as $p){
    if(count($featuredProducts) >= 4) break;
    if(!in_array($p['id'], $ids)){
      $featuredProducts[] = $p;
    }
  }
}

$faqs = [];
try{
  $stmt = $pdo->query('SELECT * FROM faqs ORDER BY id ASC LIMIT 3');
  $faqs = $stmt->fetchAll();
}catch(Exception $e){}
?>

<!-- Hero Section -->
<section class="hero-teespace" id="hero">
  <div class="hero-shape hero-shape-1"></div>
  <div class="hero-shape hero-shape-2"></div>
  <div class="hero-shape hero-shape-3"></div>
  <div class="container">
    <div class="row align-items-center g-4">
      <div class="col-lg-6">
        <h1><?php echo htmlspecialchars(getSetting('hero_title', "Let's configure you own print product")); ?> <span class="arrow-accent"></span></h1>
        <p class="lead"><?php echo htmlspecialchars(getSetting('hero_subtitle', 'The easiest way to get your print as you want')); ?></p>
        <a href="<?php echo htmlspecialchars(getSetting('hero_cta_link', 'product_list.php')); ?>" class="btn btn-print mt-3"><?php echo htmlspecialchars(getSetting('hero_cta_text', 'Print Your Own')); ?></a>
      </div>
      <div class="col-lg-6">
        <div class="hero-grid">
          <div class="hero-grid-item"><img src="<?php echo htmlspecialchars(BASE_URL.'/'.getSetting('hero_img_1', 'Image/man(1).png')); ?>" alt="T-shirt"></div>
          <div class="hero-grid-item"><img src="<?php echo htmlspecialchars(BASE_URL.'/'.getSetting('hero_img_2', 'Image/man(2).png')); ?>" alt="T-shirt mockup"></div>
          <div class="hero-grid-item"><img src="<?php echo htmlspecialchars(BASE_URL.'/'.getSetting('hero_img_3', 'Image/man(3).png')); ?>" alt="Print testing"></div>
          <div class="hero-grid-item"><img src="<?php echo htmlspecialchars(BASE_URL.'/'.getSetting('hero_img_4', 'Image/man(4).png')); ?>" alt="Model in hoodie"></div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Categories -->
<section class="categories-section" id="categories" data-animate="fade-up">
  <div class="container">
    <h2>Shopping by Categories</h2>
    <div class="row g-3 justify-content-center">
      <?php
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
      ];
      $catIconDefault = 'bi-tag';
      $categories = $pdo->query('SELECT name, (SELECT COUNT(*) FROM products WHERE category_id=c.id AND status=1) as cnt FROM categories c ORDER BY name')->fetchAll();
      $ci = 0;
      foreach($categories as $c):
        if((int)$c['cnt'] < 1) continue;
        $bg = $catColors[$ci % count($catColors)];
        $icon = $catIconMap[strtolower($c['name'])] ?? $catIconDefault;
      ?>
      <div class="col-4 col-md">
        <a href="product_list.php?cat=<?php echo htmlspecialchars($c['name']); ?>" class="cat-circle">
          <div class="cat-img-wrap" style="background:<?php echo $bg; ?>;width:56px;height:56px;display:flex;align-items:center;justify-content:center">
            <i class="bi <?php echo $icon; ?>" style="font-size:1.2rem;color:var(--text-secondary)"></i>
          </div>
          <span class="cat-label"><?php echo htmlspecialchars($c['name']); ?></span>
          <span class="cat-count"><?php echo $c['cnt']; ?></span>
        </a>
      </div>
      <?php $ci++; endforeach; ?>
    </div>
  </div>
</section>

<!-- Promo Banners -->
<section class="promo-banners" id="promo-banners" data-animate="fade-up">
  <div class="container">
    <div class="row g-3">
      <div class="col-md-6">
        <div class="promo-card promo-card-left" style="background:<?php echo htmlspecialchars(getSetting('promo1_bg', '#eef2ff')); ?>">
          <div class="promo-content">
            <h3><?php echo htmlspecialchars(getSetting('promo1_title', 'Thousands of free templates')); ?></h3>
            <p><?php echo htmlspecialchars(getSetting('promo1_text', 'Free and easy way to bring your ideas to life')); ?></p>
            <a href="<?php echo htmlspecialchars(getSetting('promo1_link', 'product_list.php')); ?>" class="btn-promo"><?php echo htmlspecialchars(getSetting('promo1_cta', 'Explore More →')); ?></a>
          </div>
          <div class="promo-visual">
            <?php $p1i = getSetting('promo1_image', ''); ?>
            <?php if($p1i): ?>
              <img src="<?php echo htmlspecialchars(BASE_URL.'/'.$p1i); ?>" alt="Promo">
            <?php else: ?>
              <img src="<?php echo BASE_URL; ?>/Image/man(10).png" alt="Templates preview">
            <?php endif; ?>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="promo-card promo-card-right" style="background:<?php echo htmlspecialchars(getSetting('promo2_bg', '#fef2f2')); ?>">
          <div class="promo-content">
            <h3><?php echo htmlspecialchars(getSetting('promo2_title', 'Summer essentials')); ?></h3>
            <p><?php echo htmlspecialchars(getSetting('promo2_text', 'Get ready for summer with our new collection')); ?></p>
            <a href="<?php echo htmlspecialchars(getSetting('promo2_link', 'product_list.php')); ?>" class="btn-promo"><?php echo htmlspecialchars(getSetting('promo2_cta', 'Shop Now →')); ?></a>
          </div>
          <div class="promo-visual">
            <?php $p2i = getSetting('promo2_image', ''); ?>
            <?php if($p2i): ?>
              <img src="<?php echo htmlspecialchars(BASE_URL.'/'.$p2i); ?>" alt="Promo">
            <?php else: ?>
              <img src="<?php echo BASE_URL; ?>/Image/man(11).png" alt="Style preview">
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- New Arrivals / Best Seller / Sale Tabs -->
<section class="product-tabs-section py-5" id="product-tabs" data-animate="fade-up">
  <div class="container">
    <div class="tab-header">
      <div class="tab-nav" id="productTabs">
        <a href="#" class="active" data-tab="new-arrivals"><?php echo htmlspecialchars(getSetting('tab1_label', 'New Arrivals')); ?></a>
        <a href="#" data-tab="best-sellers"><?php echo htmlspecialchars(getSetting('tab2_label', 'Best Seller')); ?></a>
        <a href="#" data-tab="sale"><?php echo htmlspecialchars(getSetting('tab3_label', 'Sale')); ?></a>
      </div>
      <div class="carousel-arrows" id="tabCarouselArrows">
        <a href="#" id="tabArrowLeft"><i class="bi bi-chevron-left"></i></a>
        <a href="#" id="tabArrowRight"><i class="bi bi-chevron-right"></i></a>
      </div>
    </div>

    <?php
    $tabData = [
      'new-arrivals' => ['products' => $newArrivals, 'emptyMsg' => 'No new arrivals yet.'],
      'best-sellers' => ['products' => $bestSellers, 'emptyMsg' => 'No best sellers yet.'],
      'sale'         => ['products' => $saleProducts, 'emptyMsg' => 'No sale products yet.'],
    ];
    function renderProductCard($p, $tag = ''){
      $hasSale = !empty($p['sale_price']) && $p['sale_price'] > 0;
      $displayPrice = $hasSale ? $p['sale_price'] : $p['price'];
      $oldPrice = $hasSale ? $p['price'] : null;
      $discountPct = $hasSale ? round((1 - $p['sale_price'] / $p['price']) * 100) : 0;
    ?>
      <div class="col-6 col-md-3">
        <div class="product-card-ts">
          <a href="product_detail.php?id=<?php echo $p['id']; ?>" class="text-decoration-none text-reset">
          <div class="product-img-wrap">
            <img src="<?php echo htmlspecialchars($p['image'] ?: 'assets/img/placeholder.svg'); ?>" alt="<?php echo htmlspecialchars($p['title']); ?>" onerror="this.src='assets/img/placeholder.svg'">
            <div class="product-tags">
              <?php if($tag): ?>
                <span class="tag tag-<?php echo $tag; ?>"><?php echo $tag === 'best-seller' ? 'Best Seller' : ($tag === 'new' ? 'New' : 'Sale!'); ?></span>
              <?php endif; ?>
              <?php if($hasSale): ?>
                <span class="tag tag-sale">-<?php echo $discountPct; ?>%</span>
              <?php endif; ?>
            </div>
          </div>
          <div class="product-body">
            <div class="product-title"><?php echo htmlspecialchars(isset($p['category']) ? $p['category'] : 'Category'); ?></div>
            <div class="product-name"><?php echo htmlspecialchars($p['title']); ?></div>
            <div class="product-price">
              <?php echo formatPrice($displayPrice); ?>
              <?php if($oldPrice): ?><span class="old-price"><?php echo formatPrice($oldPrice); ?></span><?php endif; ?>
            </div>
          </div>
          </a>
        </div>
      </div>
    <?php
    }
    $tabTags = ['new-arrivals' => 'new', 'best-sellers' => 'best-seller', 'sale' => 'sale'];
    foreach($tabData as $tabKey => $tab): ?>
      <div class="row g-3 tab-pane <?php echo $tabKey === 'new-arrivals' ? 'active' : ''; ?>" data-tab="<?php echo $tabKey; ?>">
        <?php if(!$tab['products']): ?>
          <div class="col-12">
            <div class="card p-5 text-center">
              <i class="bi bi-box" style="font-size:3rem;color:#94a3b8"></i>
              <p class="mt-2 text-muted"><?php echo $tab['emptyMsg']; ?></p>
              <a href="admin/products.php" class="btn btn-primary w-auto mx-auto">Go to Admin</a>
            </div>
          </div>
        <?php else: foreach($tab['products'] as $p): ?>
          <?php renderProductCard($p, $tabTags[$tabKey]); ?>
        <?php endforeach; endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- Featured Products -->
<section class="featured-section" id="featured-products" data-animate="fade-up">
  <div class="container">
    <div class="section-header">
      <h2><?php echo htmlspecialchars(getSetting('hot_title', 'Featured Products')); ?></h2>
      <a href="<?php echo htmlspecialchars(getSetting('hot_link', 'product_list.php')); ?>" class="view-all"><?php echo htmlspecialchars(getSetting('hot_link_text', 'View All →')); ?></a>
    </div>
    <div class="row g-3">
      <?php if($featuredProducts): foreach($featuredProducts as $p):
        $fHasSale = !empty($p['sale_price']) && $p['sale_price'] > 0;
        $fPrice = $fHasSale ? $p['sale_price'] : $p['price'];
        $fOld = $fHasSale ? $p['price'] : null;
        $fDisc = $fHasSale ? round((1 - $p['sale_price'] / $p['price']) * 100) : 0;
      ?>
        <div class="col-6 col-md-3">
          <div class="product-card-ts">
            <a href="product_detail.php?id=<?php echo $p['id']; ?>" class="text-decoration-none text-reset">
            <div class="product-img-wrap">
              <img src="<?php echo htmlspecialchars($p['image'] ?: 'assets/img/placeholder.svg'); ?>" alt="<?php echo htmlspecialchars($p['title']); ?>" onerror="this.src='assets/img/placeholder.svg'">
              <div class="product-tags">
                <?php if($fHasSale): ?><span class="tag tag-sale">-<?php echo $fDisc; ?>%</span><?php endif; ?>
              </div>
            </div>
            <div class="product-body">
              <div class="product-title"><?php echo htmlspecialchars(isset($p['category']) ? $p['category'] : 'Category'); ?></div>
              <div class="product-name"><?php echo htmlspecialchars($p['title']); ?></div>
              <div class="product-price">
                <?php echo formatPrice($fPrice); ?>
                <?php if($fOld): ?><span class="old-price"><?php echo formatPrice($fOld); ?></span><?php endif; ?>
              </div>
            </div>
            </a>
          </div>
        </div>
      <?php endforeach; else: ?>
        <div class="col-12 text-center py-4 text-muted">
          <i class="bi bi-box" style="font-size:2rem"></i>
          <p class="mt-2">Featured products coming soon.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- FAQ -->
<?php
$faqStmt = $pdo->query('SELECT * FROM faqs ORDER BY id DESC LIMIT 6');
$homeFaqs = $faqStmt->fetchAll();
?>
<section class="featured-section" id="faq" style="background:var(--bg-secondary)" data-animate="fade-up">
  <div class="container">
    <div class="text-center mb-4">
      <h2 class="fw-bold">Frequently Asked Questions</h2>
      <p class="text-muted">Quick answers to common questions about our products.</p>
    </div>
    <?php if(!$homeFaqs): ?>
      <div class="card p-5 text-center">
        <i class="bi bi-inbox" style="font-size:3rem;color:#94a3b8"></i>
        <h5 class="mt-3">No FAQs yet</h5>
        <p class="text-muted mb-0">Check back later for updates.</p>
      </div>
    <?php else: ?>
      <div class="row justify-content-center">
        <div class="col-lg-8">
          <div class="accordion" id="homeFaqs">
            <?php foreach($homeFaqs as $f): ?>
              <div class="accordion-item shadow-sm">
                <h2 class="accordion-header">
                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#hf<?php echo $f['id']; ?>">
                    <?php echo htmlspecialchars($f['question']); ?>
                  </button>
                </h2>
                <div id="hf<?php echo $f['id']; ?>" class="accordion-collapse collapse" data-bs-parent="#homeFaqs">
                  <div class="accordion-body"><?php echo nl2br(htmlspecialchars($f['answer'])); ?></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php
// Latest blog posts for homepage
$latestPosts = [];
try {
  $stmt = $pdo->query("SELECT id, title, slug, excerpt, image, author, created_at FROM blog_posts WHERE status=1 ORDER BY created_at DESC LIMIT 3");
  $latestPosts = $stmt->fetchAll();
} catch (Exception $e) { $latestPosts = []; }
?>
<?php if ($latestPosts): ?>
<section class="blog-section" id="blog" data-animate="fade-up">
  <div class="container">
    <div class="section-header">
      <h2>Latest from Blog</h2>
      <a href="blog.php" class="view-all">View All Blog Posts →</a>
    </div>
    <div class="row g-4">
      <?php foreach ($latestPosts as $post): ?>
      <div class="col-md-6 col-lg-4">
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
  </div>
</section>
<?php endif; ?>

<!-- Free Design Templates -->
<section class="templates-section" id="templates" data-animate="fade-up">
  <div class="container">
    <div class="section-header">
      <h2><?php echo htmlspecialchars(getSetting('templates_heading', 'Free design templates')); ?></h2>
      <a href="<?php echo htmlspecialchars(getSetting('templates_view_all_link', 'product_list.php')); ?>" class="view-all"><?php echo htmlspecialchars(getSetting('templates_view_all_text', 'View All')); ?> →</a>
    </div>
    <div class="row g-3 templates-grid">
      <?php
      $tmplDefaults = [
        1 => ['label' => 'Astronauts',        'count' => '85 resources',       'img' => 'Image/man(12).png'],
        2 => ['label' => 'Quote that collection', 'count' => '6 resources',   'img' => 'Image/man(13).png'],
        3 => ['label' => 'Art Styles',           'count' => '68 resources',   'img' => 'Image/man(14).png'],
        4 => ['label' => '+28 Collections',      'count' => 'View all',        'img' => 'Image/man(15).png'],
      ];
      for ($i = 1; $i <= 4; $i++):
        $d = $tmplDefaults[$i];
        $label = getSetting('tmpl'.$i.'_label', $d['label']);
        $count = getSetting('tmpl'.$i.'_count', $d['count']);
        $img   = getSetting('tmpl'.$i.'_image', $d['img']);
      ?>
      <div class="col-6 col-md-3">
        <div class="tmpl-card">
          <img src="<?php echo htmlspecialchars(BASE_URL.'/'.$img); ?>" alt="<?php echo htmlspecialchars($label); ?>">
          <div class="tmpl-overlay">
            <div class="tmpl-label"><?php echo htmlspecialchars($label); ?></div>
            <?php if ($count): ?><div class="tmpl-count"><?php echo htmlspecialchars($count); ?></div><?php endif; ?>
          </div>
        </div>
      </div>
      <?php endfor; ?>
    </div>
  </div>
</section>

<!-- Testimonials -->
<section class="app-testimonial-section" id="testimonials">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-8" data-animate="fade-up">
        <div class="testimonial-slider">
          <div class="testimonial-slider-track">
            <div class="testimonial-card testimonial-slide">
              <div class="quote-mark">"</div>
              <blockquote><?php echo htmlspecialchars(getSetting('testimonial_quote_1', 'For all your printing prerequisites. Offer to make their pamphlets, business cards, solicitations, and occasion programs.')); ?></blockquote>
              <div class="testimonial-author">
                <div class="avatar"><?php echo htmlspecialchars(substr(getSetting('testimonial_name_1', 'Eddy M.'), 0, 2)); ?></div>
                <div>
                  <div class="author-name"><?php echo htmlspecialchars(getSetting('testimonial_name_1', 'Eddy M.')); ?></div>
                  <div class="author-title"><?php echo htmlspecialchars(getSetting('testimonial_title_1', 'Designer at Lift')); ?></div>
                </div>
              </div>
            </div>
            <div class="testimonial-card testimonial-slide">
              <div class="quote-mark">"</div>
              <blockquote><?php echo htmlspecialchars(getSetting('testimonial_quote_2', 'The quality exceeded my expectations. Fast turnaround and excellent customer support throughout the entire process.')); ?></blockquote>
              <div class="testimonial-author">
                <div class="avatar"><?php echo htmlspecialchars(substr(getSetting('testimonial_name_2', 'Sarah K.'), 0, 2)); ?></div>
                <div>
                  <div class="author-name"><?php echo htmlspecialchars(getSetting('testimonial_name_2', 'Sarah K.')); ?></div>
                  <div class="author-title"><?php echo htmlspecialchars(getSetting('testimonial_title_2', 'Owner at Bright Prints')); ?></div>
                </div>
              </div>
            </div>
            <div class="testimonial-card testimonial-slide">
              <div class="quote-mark">"</div>
              <blockquote><?php echo htmlspecialchars(getSetting('testimonial_quote_3', 'I have been using their service for over a year now. Consistent quality, great pricing, and my customers love the results every time.')); ?></blockquote>
              <div class="testimonial-author">
                <div class="avatar"><?php echo htmlspecialchars(substr(getSetting('testimonial_name_3', 'James R.'), 0, 2)); ?></div>
                <div>
                  <div class="author-name"><?php echo htmlspecialchars(getSetting('testimonial_name_3', 'James R.')); ?></div>
                  <div class="author-title"><?php echo htmlspecialchars(getSetting('testimonial_title_3', 'Creative Director, Merge')); ?></div>
                </div>
              </div>
            </div>
          </div>
          <div class="slider-dots">
            <span class="active" data-index="0"></span>
            <span data-index="1"></span>
            <span data-index="2"></span>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Newsletter -->
<section class="newsletter-teespace py-5" id="newsletter" data-animate="fade-up">
  <div class="container">
    <h2><?php echo htmlspecialchars(getSetting('newsletter_heading', 'Get the latest news, events & more delivered to your inbox.')); ?></h2>
    <form class="newsletter-form" method="POST" action="newsletter_subscribe.php">
      <?php echo csrfFieldFront(); ?>
      <input type="email" name="email" placeholder="Your email address" required>
      <button type="submit"><i class="bi bi-arrow-right"></i></button>
    </form>
  </div>
</section>

<!-- Instagram Gallery -->
<section class="insta-gallery" id="instagram">
  <?php
  $instaDefaults = ['Image/man(17).png', 'Image/man(18).png', 'Image/man(19).png', 'Image/man(20).png', 'Image/man(21).png'];
  for ($i = 1; $i <= 5; $i++):
    $img = getSetting('insta_img_'.$i, $instaDefaults[$i-1]);
  ?>
  <div class="insta-item">
    <img src="<?php echo htmlspecialchars(BASE_URL.'/'.$img); ?>" alt="Instagram">
    <div class="insta-overlay"><i class="bi bi-instagram"></i></div>
  </div>
  <?php endfor; ?>
</section>

<?php require_once __DIR__.'/templates/footer.php'; ?>