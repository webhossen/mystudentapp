<?php
require_once __DIR__.'/config/config.php';
require_once __DIR__.'/templates/header.php';

$id = isset($_GET['id'])?intval($_GET['id']):0;
$product = null;
$pdo = getPDO();
try{
  if($id){
    $stmt = $pdo->prepare('SELECT p.*, c.name as category FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.id=?');
    $stmt->execute([$id]);
    $product = $stmt->fetch();
  }
}catch(Exception $e){}

$sizesArr = [];
if($product && !empty($product['sizes'])){
  $sizesArr = array_map('trim', explode(',', $product['sizes']));
}

$reviews = [];
$avgRating = 0;
$reviewCount = 0;
if ($product) {
  try {
    $stmt = $pdo->prepare('SELECT r.*, u.name FROM product_reviews r JOIN users u ON u.id=r.user_id WHERE r.product_id=? AND r.status=1 ORDER BY r.id DESC');
    $stmt->execute([$id]);
    $reviews = $stmt->fetchAll();
    $reviewCount = count($reviews);
    if ($reviewCount > 0) {
      $avgRating = round(array_sum(array_column($reviews, 'rating')) / $reviewCount, 1);
    }
  } catch(Exception $e){}
}

function renderStars($rating, $small = true) {
  $s = $small ? 'small' : '';
  $html = "<div class=\"text-warning $s\">";
  for ($i = 1; $i <= 5; $i++) {
    if ($i <= $rating) {
      $html .= '<i class="bi bi-star-fill"></i>';
    } elseif ($i - 0.5 <= $rating) {
      $html .= '<i class="bi bi-star-half"></i>';
    } else {
      $html .= '<i class="bi bi-star"></i>';
    }
  }
  $html .= '</div>';
  return $html;
}

$related = [];
if($product){
  try{
    $sameCat = $pdo->prepare('SELECT p.id,p.title,p.price,p.sale_price,p.image,c.name as category FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.id != ? AND p.status=1 AND p.category_id = ? ORDER BY p.id DESC LIMIT 4');
    $sameCat->execute([$id, $product['category_id']]);
    $related = $sameCat->fetchAll();
    if(count($related) < 4){
      $fallback = $pdo->prepare('SELECT p.id,p.title,p.price,p.sale_price,p.image,c.name as category FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.id != ? AND p.status=1 ORDER BY p.id DESC LIMIT ?');
      $fallback->execute([$id, 4 - count($related)]);
      $related = array_merge($related, $fallback->fetchAll());
    }
  }catch(Exception $e){}
}
?>
<?php if(!$product): ?>
  <div class="card p-5 text-center">
    <i class="bi bi-exclamation-circle" style="font-size:3rem;color:#94a3b8"></i>
    <h5 class="mt-3">Product not found</h5>
    <a href="product_list.php" class="btn btn-primary mt-2 w-auto mx-auto">Browse Products</a>
  </div>
<?php else:
  $img = htmlspecialchars($product['image'] ?: 'assets/img/placeholder.svg');
$descImg = htmlspecialchars($product['description_image'] ?: '');
  $title = htmlspecialchars($product['title']);
  $cat = htmlspecialchars($product['category'] ?: 'T-SHIRT');
  $price = $product['price'];
  $desc = htmlspecialchars($product['short_description'] ?: 'Premium quality product designed for comfort and style.');
  $fullDesc = nl2br(htmlspecialchars($product['description'] ?: ''));
  $hasSale = !empty($product['sale_price']) && $product['sale_price'] > 0;
  if($hasSale){
    $salePrice = $price;
    $price = $product['sale_price'];
  } else {
    $salePrice = null;
  }
?>
  <!-- Breadcrumb -->
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb small">
      <li class="breadcrumb-item"><a href="index.php">Home</a></li>
      <li class="breadcrumb-item"><a href="product_list.php">Shop</a></li>
      <li class="breadcrumb-item"><a href="product_list.php?cat=<?php echo urlencode($product['category'] ?? 'apparel'); ?>"><?php echo $cat; ?></a></li>
      <li class="breadcrumb-item active"><?php echo $title; ?></li>
    </ol>
  </nav>

  <!-- Main Product Section -->
  <div class="row g-4 mb-5">
    <!-- Left: Image Gallery -->
    <div class="col-lg-5">
      <div class="main-image-wrap position-relative" style="max-width:100%">
        <img id="mainProductImg" src="<?php echo $img; ?>" alt="<?php echo $title; ?>" class="w-100 rounded" style="max-height:500px;object-fit:contain" onerror="this.src='assets/img/placeholder.svg'">
        <div class="product-tags" style="position:absolute;top:12px;left:12px;z-index:2;display:flex;flex-direction:column;gap:4px">
          <?php if($hasSale): ?><span class="tag tag-sale">-<?php echo round((1 - $product['sale_price'] / $product['price']) * 100); ?>%</span><?php endif; ?>
          <span class="tag tag-new">New</span>
        </div>
      </div>
    </div>

    <!-- Right: Product Configurator -->
    <div class="col-lg-7">
      <div class="product-meta text-uppercase small text-muted fw-semibold mb-1"><?php echo $cat; ?> / NEW ARRIVAL</div>
      <h1 class="fw-bold display-6 mb-2" style="font-size:2rem"><?php echo $title; ?></h1>

      <!-- Rating -->
      <div class="d-flex align-items-center gap-2 mb-3">
        <?php echo renderStars($avgRating); ?>
        <a href="#reviews" class="small text-decoration-none" data-bs-toggle="tab">(<?php echo $reviewCount; ?> customer review<?php echo $reviewCount !== 1 ? 's' : ''; ?>)</a>
      </div>

      <!-- Price -->
      <div class="price-area mb-3">
        <span class="fw-bold fs-3"><?php echo formatPrice($price); ?></span>
        <?php if($salePrice): ?><span class="text-decoration-line-through text-muted ms-2"><?php echo formatPrice($salePrice); ?></span><?php endif; ?>
      </div>

      <!-- Short Description -->
      <p class="text-muted mb-3" style="font-size:0.92rem;line-height:1.6"><?php echo $desc; ?></p>

      <?php if (!empty($sizesArr)): ?>
      <!-- Size Selection -->
      <div class="mb-3">
        <label class="form-label fw-semibold">Size: <span id="selectedSizeLabel"><?php echo htmlspecialchars($sizesArr[0]); ?></span></label>
        <div class="d-flex gap-2 flex-wrap" id="sizeOptions">
          <?php foreach ($sizesArr as $sz): ?>
          <span class="size-option <?php echo $sz === $sizesArr[0] ? 'active' : ''; ?>" data-size="<?php echo htmlspecialchars($sz); ?>"><?php echo htmlspecialchars($sz); ?></span>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Quantity & Add to Cart -->
      <form method="get" action="cart.php" class="d-flex align-items-center gap-3 mt-4">
        <input type="hidden" name="add" value="<?php echo $product['id']; ?>">
        <input type="hidden" name="qty" id="qtyInput" value="1">
        <input type="hidden" name="size" id="selectedSizeInput" value="<?php echo htmlspecialchars($sizesArr[0] ?? ''); ?>">
        <div class="qty-selector d-flex align-items-center border rounded" style="border-color:var(--border)">
          <button type="button" class="qty-btn btn btn-sm border-0 px-3 fs-5" onclick="adjustQty(-1)">−</button>
          <span id="qtyDisplay" class="px-3 fw-semibold">1</span>
          <button type="button" class="qty-btn btn btn-sm border-0 px-3 fs-5" onclick="adjustQty(1)">+</button>
        </div>
        <button type="submit" class="btn btn-print flex-fill text-center" style="background:var(--primary);color:#fff;border:none;padding:0.7rem 1.5rem;border-radius:var(--radius-full);font-weight:600;font-size:0.95rem;text-decoration:none"><i class="bi bi-cart-plus me-1"></i> Add to Cart</button>
        <?php $inWishlist = isset($_SESSION['wishlist'][$product['id']]); ?>
        <span class="btn border rounded-circle d-flex align-items-center justify-content-center wl-toggle" data-id="<?php echo $product['id']; ?>" style="width:48px;height:48px;flex-shrink:0;border-color:var(--border);color:var(--text-secondary);cursor:pointer" title="<?php echo $inWishlist ? 'Remove from Wishlist' : 'Add to Wishlist'; ?>"><i class="bi <?php echo $inWishlist ? 'bi-heart-fill text-danger' : 'bi-heart'; ?>"></i></span>
      </form>
    </div>
  </div>

  <!-- Product Info Tabs -->
  <hr class="my-0">
  <div class="product-info-tabs py-5">
    <ul class="nav nav-tabs border-0 justify-content-center gap-4 mb-4" id="productTab" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active border-0 bg-transparent fw-semibold px-0" style="color:var(--text);border-bottom:2px solid var(--primary);border-radius:0" id="desc-tab" data-bs-toggle="tab" data-bs-target="#desc" type="button">Description</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link border-0 bg-transparent fw-semibold px-0" style="color:var(--muted);border-radius:0" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button">Additional Information</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link border-0 bg-transparent fw-semibold px-0" style="color:var(--muted);border-radius:0" id="reviews-tab" data-bs-toggle="tab" data-bs-target="#reviews" type="button">Reviews (<?php echo $reviewCount; ?>)</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link border-0 bg-transparent fw-semibold px-0" style="color:var(--muted);border-radius:0" id="shipping-tab" data-bs-toggle="tab" data-bs-target="#shipping" type="button">Shipping & Delivery</button>
      </li>
    </ul>
    <div class="tab-content" id="productTabContent">
      <div class="tab-pane fade show active" id="desc">
        <div class="row g-4 align-items-start">
          <div class="col-md-7">
          <?php if($fullDesc): ?>
            <?php echo $fullDesc; ?>
          <?php else: ?>
            <ul class="list-unstyled" style="color:var(--text-secondary);line-height:2">
              <li><i class="bi bi-check2 text-primary me-2"></i>Premium 100% organic cotton jersey knit</li>
              <li><i class="bi bi-check2 text-primary me-2"></i>Mid-weight 5.3 oz / 180 gsm fabric</li>
              <li><i class="bi bi-check2 text-primary me-2"></i>Relaxed boxy fit with dropped shoulders</li>
              <li><i class="bi bi-check2 text-primary me-2"></i>Direct-to-Garment (DTG) print technology</li>
              <li><i class="bi bi-check2 text-primary me-2"></i>Machine wash cold, tumble dry low</li>
              <li><i class="bi bi-check2 text-primary me-2"></i>Double-needle topstitch hem and sleeves</li>
            </ul>
          <?php endif; ?>
          </div>
          <?php if($descImg): ?>
          <div class="col-md-5">
            <img src="<?php echo BASE_URL; ?>/<?php echo $descImg; ?>" alt="<?php echo $title; ?> detail" class="w-100 rounded" style="max-height:350px;object-fit:contain">
          </div>
          <?php endif; ?>
        </div>
      </div>
      <div class="tab-pane fade" id="info">
        <div class="row g-4">
          <div class="col-md-6">
            <table class="table table-borderless" style="color:var(--text-secondary)">
              <tr><td class="ps-0 fw-semibold" style="width:140px;color:var(--text)">Weight</td><td>5.3 oz / 180 gsm</td></tr>
              <tr><td class="ps-0 fw-semibold" style="color:var(--text)">Fabric</td><td>100% Organic Cotton</td></tr>
              <tr><td class="ps-0 fw-semibold" style="color:var(--text)">Fit</td><td>Relaxed / Boxy</td></tr>
              <tr><td class="ps-0 fw-semibold" style="color:var(--text)">Neck</td><td>Crew neck</td></tr>
            </table>
          </div>
          <div class="col-md-6">
            <table class="table table-borderless" style="color:var(--text-secondary)">
              <tr><td class="ps-0 fw-semibold" style="width:140px;color:var(--text)">Print Method</td><td>DTG</td></tr>
              <tr><td class="ps-0 fw-semibold" style="color:var(--text)">Care</td><td>Machine wash cold</td></tr>
              <tr><td class="ps-0 fw-semibold" style="color:var(--text)">Origin</td><td>Made in USA</td></tr>
              <tr><td class="ps-0 fw-semibold" style="color:var(--text)">SKU</td><td>TS-<?php echo str_pad($product['id'], 4, '0', STR_PAD_LEFT); ?></td></tr>
            </table>
          </div>
        </div>
      </div>
      <div class="tab-pane fade" id="reviews">
        <?php if ($reviews): ?>
        <div class="row g-3 mb-4">
          <?php foreach ($reviews as $rv):
            $initials = strtoupper(substr($rv['name'], 0, 1) . (strpos($rv['name'], ' ') !== false ? substr($rv['name'], strpos($rv['name'], ' ') + 1, 1) : ''));
            $colors = ['bg-primary','bg-success','bg-warning text-dark','bg-info','bg-danger','bg-secondary'];
            $color = $colors[crc32($rv['name']) % count($colors)];
          ?>
          <div class="col-md-6">
            <div class="card border-0 bg-light p-3 h-100">
              <div class="d-flex gap-3">
                <div class="rounded-circle <?php echo $color; ?> text-white d-flex align-items-center justify-content-center" style="width:40px;height:40px;font-weight:700;flex-shrink:0;font-size:0.85rem"><?php echo htmlspecialchars($initials); ?></div>
                <div class="flex-grow-1">
                  <div class="d-flex align-items-center gap-2 mb-1">
                    <?php echo renderStars($rv['rating']); ?>
                    <small class="text-muted"><?php echo date('M j, Y', strtotime($rv['created_at'])); ?></small>
                  </div>
                  <p class="mb-1 small"><?php echo nl2br(htmlspecialchars($rv['review'])); ?></p>
                  <div class="small text-muted">— <?php echo htmlspecialchars($rv['name']); ?> <i class="bi bi-patch-check-fill text-primary"></i> Verified Purchase</div>
                </div>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="text-center py-4 text-muted">
          <i class="bi bi-chat-square-text" style="font-size:2rem"></i>
          <p class="mt-2 mb-0">No reviews yet. Be the first to review this product!</p>
        </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['user_id'])): ?>
        <hr class="my-4">
        <h6 class="fw-bold mb-3"><i class="bi bi-pencil-square me-1"></i>Write a Review</h6>
        <form method="POST" action="submit_review.php" class="row g-3" style="max-width:600px">
          <?php echo csrfFieldFront(); ?>
          <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
          <div class="col-12">
            <label class="form-label fw-medium small">Your Rating</label>
            <div class="rating-stars d-flex gap-1 fs-4">
              <?php for ($i = 1; $i <= 5; $i++): ?>
              <i class="bi bi-star text-warning" data-rating="<?php echo $i; ?>" style="cursor:pointer;transition:all 0.15s"></i>
              <?php endfor; ?>
            </div>
            <input type="hidden" name="rating" id="ratingInput" value="5">
          </div>
          <div class="col-12">
            <label class="form-label fw-medium small">Your Review</label>
            <textarea class="form-control" name="review" rows="3" placeholder="Share your experience with this product..." required></textarea>
          </div>
          <div class="col-12">
            <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-send me-1"></i>Submit Review</button>
          </div>
        </form>
        <script>
        document.querySelectorAll('.rating-stars i').forEach(function(star){
          star.addEventListener('click', function(){
            var val = parseInt(this.dataset.rating);
            document.getElementById('ratingInput').value = val;
            document.querySelectorAll('.rating-stars i').forEach(function(s, idx){
              s.className = 'bi ' + (idx < val ? 'bi-star-fill' : 'bi-star') + ' text-warning';
            });
          });
        });
        </script>
        <?php else: ?>
        <hr class="my-4">
        <div class="text-center py-3">
          <p class="text-muted mb-2">Please <a href="login.php">login</a> to leave a review.</p>
        </div>
        <?php endif; ?>
      </div>
      <div class="tab-pane fade" id="shipping">
        <div class="p-3 text-center" style="color:var(--text-secondary);max-width:600px;margin:0 auto">
          <i class="bi bi-truck fs-1 text-primary mb-3 d-block"></i>
          <h5 class="fw-bold">Free Standard Shipping</h5>
          <p>On all orders over $50. Estimated delivery: 5–8 business days.</p>
          <p class="small">Express shipping available at checkout. International shipping rates vary by destination.</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Upsell Banner -->
  <section class="rounded p-4 mb-5 d-flex align-items-center justify-content-between flex-wrap gap-3 upsell-banner" style="background:var(--surface-soft)">
    <div>
      <h4 class="fw-bold mb-1" style="font-size:1.2rem">Want to put your own design on this shirt?</h4>
      <p class="mb-0 small text-muted">Use our customizer to create something unique.</p>
    </div>
    <a href="#" class="btn btn-print" style="background:var(--primary);color:#fff;border:none;padding:0.5rem 1.5rem;border-radius:var(--radius-full);font-weight:600;font-size:0.88rem;text-decoration:none;white-space:nowrap">Open Customizer</a>
  </section>

  <!-- Related Products -->
  <?php if($related): ?>
    <section class="mb-5">
      <h3 class="fw-bold mb-4" style="font-size:1.4rem">Related Products</h3>
      <div class="row g-3">
        <?php foreach($related as $r): ?>
          <?php
            $rHasSale = !empty($r['sale_price']) && $r['sale_price'] > 0;
            $rDisplay = $rHasSale ? $r['sale_price'] : $r['price'];
            $rOld = $rHasSale ? $r['price'] : null;
            $rDisc = $rHasSale ? round((1 - $r['sale_price'] / $r['price']) * 100) : 0;
          ?>
          <div class="col-6 col-md-3">
            <div class="product-card-ts">
              <a href="product_detail.php?id=<?php echo $r['id']; ?>" class="text-decoration-none text-reset">
              <div class="product-img-wrap">
                <img src="<?php echo htmlspecialchars($r['image'] ?: 'assets/img/placeholder.svg'); ?>" alt="<?php echo htmlspecialchars($r['title']); ?>" onerror="this.src='assets/img/placeholder.svg'">
                <?php if($rHasSale): ?>
                <div class="product-tags">
                  <span class="tag tag-sale">-<?php echo $rDisc; ?>%</span>
                </div>
                <?php endif; ?>
              </div>
              <div class="product-body">
                <div class="product-title"><?php echo htmlspecialchars($r['category'] ?? 'Apparel'); ?></div>
                <div class="product-name"><?php echo htmlspecialchars($r['title']); ?></div>
                <div class="product-price"><?php echo formatPrice($rDisplay); ?>
                  <?php if($rOld): ?><span class="old-price"><?php echo formatPrice($rOld); ?></span><?php endif; ?>
                </div>
              </div>
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>
  <script>
  function adjustQty(delta) {
    let el = document.getElementById('qtyDisplay');
    let inp = document.getElementById('qtyInput');
    let v = parseInt(el.textContent) + delta;
    if (v < 1) v = 1;
    if (v > 99) v = 99;
    el.textContent = v;
    if(inp) inp.value = v;
  }

  function sizeClickHandler(){
    document.querySelectorAll('.size-option').forEach(function(x){
      x.classList.remove('active');
    });
    this.classList.add('active');
    document.getElementById('selectedSizeLabel').textContent = this.getAttribute('data-size');
    document.getElementById('selectedSizeInput').value = this.getAttribute('data-size');
  }
  document.querySelectorAll('.size-option').forEach(function(el){
    el.addEventListener('click', sizeClickHandler);
  });
  </script>
<?php endif; ?>

<script>
document.querySelectorAll('.wl-toggle').forEach(function(el){
  el.addEventListener('click', function(e){
    e.stopPropagation();
    var id = this.dataset.id;
    var icon = this.querySelector('i');
    var isFilled = icon.classList.contains('bi-heart-fill');
    var action = isFilled ? 'remove_wishlist' : 'add_wishlist';
    fetch('cart.php?' + action + '=' + id + '&ajax=1').then(function(r){ return r.json(); }).then(function(d){
      if(d.success){
        icon.className = 'bi ' + (isFilled ? 'bi-heart' : 'bi-heart-fill text-danger');
        el.title = isFilled ? 'Add to Wishlist' : 'Remove from Wishlist';
        var badge = document.querySelector('.header-icons a[title="Wishlist"] .badge-count');
        if(badge) badge.textContent = d.wishlistCount;
      }
    });
  });
});
</script>
<?php require_once __DIR__.'/templates/footer.php'; ?>