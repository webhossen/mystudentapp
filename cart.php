<?php
require_once __DIR__.'/config/config.php';
session_start();
$cart = $_SESSION['cart'] ?? [];
$wishlist = $_SESSION['wishlist'] ?? [];
$isWishlist = isset($_GET['wishlist']);

$isAjax = isset($_GET['ajax']);

// Add CSRF check for state-changing operations
function cartCsrfToken(): string {
    if (empty($_SESSION['cart_csrf_token'])) {
        $_SESSION['cart_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['cart_csrf_token'];
}

function verifyCartCsrf(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
    $token = $_POST['_cart_csrf'] ?? '';
    if (empty($_SESSION['cart_csrf_token']) || !hash_equals($_SESSION['cart_csrf_token'], $token)) {
        http_response_code(403);
        if (isset($_GET['ajax'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid request.']);
            exit;
        }
        header('Location: cart.php');
        exit;
    }
}

// Handle GET actions (add, add_wishlist, remove_wishlist)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['add'])) {
        $id = intval($_GET['add']);
        $qty = max(1, intval($_GET['qty'] ?? 1));
        $cart[$id] = ($cart[$id] ?? 0) + $qty;
        $_SESSION['cart'] = $cart;
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success'=>true,'cartCount'=>array_sum($cart),'wishlistCount'=>count($wishlist)]);
            exit;
        }
        header('Location: cart.php'); exit;
    }
    if (isset($_GET['add_wishlist'])) {
        $id = intval($_GET['add_wishlist']);
        $wishlist[$id] = true;
        $_SESSION['wishlist'] = $wishlist;
        header('Content-Type: application/json');
        echo json_encode(['success'=>true,'inWishlist'=>true,'cartCount'=>array_sum($cart),'wishlistCount'=>count($wishlist)]);
        exit;
    }
    if (isset($_GET['remove_wishlist'])) {
        $id = intval($_GET['remove_wishlist']);
        unset($wishlist[$id]);
        $_SESSION['wishlist'] = $wishlist;
        header('Content-Type: application/json');
        echo json_encode(['success'=>true,'inWishlist'=>false,'cartCount'=>array_sum($cart),'wishlistCount'=>count($wishlist)]);
        exit;
    }
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCartCsrf();

    if (isset($_POST['add'])) {
        $id = intval($_POST['add']);
        $qty = max(1, intval($_POST['qty'] ?? 1));
        $cart[$id] = ($cart[$id] ?? 0) + $qty;
        $_SESSION['cart'] = $cart;
        if($isAjax){ header('Content-Type: application/json'); echo json_encode(['success'=>true,'cartCount'=>array_sum($cart),'wishlistCount'=>count($wishlist)]); exit; }
        header('Location: cart.php'); exit;
    }

    if (isset($_POST['remove'])) {
        $id = intval($_POST['remove']);
        unset($cart[$id]);
        $_SESSION['cart'] = $cart;
        if($isAjax){ header('Content-Type: application/json'); echo json_encode(['success'=>true,'cartCount'=>array_sum($cart),'wishlistCount'=>count($wishlist)]); exit; }
        header('Location: cart.php'); exit;
    }

    if (isset($_POST['add_wishlist'])) {
        $id = intval($_POST['add_wishlist']);
        $wishlist[$id] = true;
        $_SESSION['wishlist'] = $wishlist;
        if($isAjax){ header('Content-Type: application/json'); echo json_encode(['success'=>true,'inWishlist'=>true,'cartCount'=>array_sum($cart),'wishlistCount'=>count($wishlist)]); exit; }
        $redirect = $_POST['redirect'] ?? ($isWishlist ? 'cart.php?wishlist=1' : 'cart.php');
        $allowedPaths = ['cart.php', 'cart.php?wishlist=1', 'product_list.php'];
        if (!in_array($redirect, $allowedPaths)) $redirect = 'cart.php';
        header('Location: ' . $redirect); exit;
    }

    if (isset($_POST['remove_wishlist'])) {
        $id = intval($_POST['remove_wishlist']);
        unset($wishlist[$id]);
        $_SESSION['wishlist'] = $wishlist;
        if($isAjax){ header('Content-Type: application/json'); echo json_encode(['success'=>true,'inWishlist'=>false,'cartCount'=>array_sum($cart),'wishlistCount'=>count($wishlist)]); exit; }
        $redirect = $_POST['redirect'] ?? ($isWishlist ? 'cart.php?wishlist=1' : 'cart.php');
        $allowedPaths = ['cart.php', 'cart.php?wishlist=1', 'product_list.php'];
        if (!in_array($redirect, $allowedPaths)) $redirect = 'cart.php';
        header('Location: ' . $redirect); exit;
    }

    if (isset($_POST['update']) && !$isWishlist){
        foreach($_POST['qty'] ?? [] as $pid => $qty){
            $q = intval($qty);
            if($q > 0) $cart[intval($pid)] = $q;
            else unset($cart[intval($pid)]);
        }
        $_SESSION['cart'] = $cart;
        header('Location: cart.php'); exit;
    }
}

$pdo = getPDO();
$items = [];
$total = 0;

$ids = $isWishlist ? array_keys($wishlist) : array_keys($cart);
if(!empty($ids)){
    $ph = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT id, title, price, sale_price, image FROM products WHERE id IN ($ph) AND status=1");
    $stmt->execute($ids);
    foreach($stmt->fetchAll() as $row){
        if($isWishlist){
            $displayPrice = (!empty($row['sale_price']) && $row['sale_price'] > 0) ? $row['sale_price'] : $row['price'];
            $items[] = ['product' => $row, 'price' => $displayPrice];
        } else {
            $qty = $cart[$row['id']];
            $unitPrice = (!empty($row['sale_price']) && $row['sale_price'] > 0) ? $row['sale_price'] : $row['price'];
            $subtotal = $unitPrice * $qty;
            $total += $subtotal;
            $items[] = ['product' => $row, 'qty' => $qty, 'subtotal' => $subtotal, 'unit_price' => $unitPrice];
        }
    }
}

require_once __DIR__.'/templates/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <?php if($isWishlist): ?>
    <h2 class="fw-bold mb-0"><i class="bi bi-heart me-2"></i>Your Wishlist</h2>
    <a href="cart.php" class="btn btn-outline-primary btn-sm"><i class="bi bi-cart3 me-1"></i>View Cart</a>
  <?php else: ?>
    <h2 class="fw-bold mb-0"><i class="bi bi-cart3 me-2"></i>Your Cart</h2>
    <span class="badge bg-primary rounded-pill fs-6"><?php echo count($items); ?> items</span>
  <?php endif; ?>
</div>
<?php if(empty($items)): ?>
  <div class="card p-5 text-center">
    <i class="bi <?php echo $isWishlist ? 'bi-heartbreak' : 'bi-cart-x'; ?>" style="font-size:3rem;color:#94a3b8"></i>
    <h5 class="mt-3"><?php echo $isWishlist ? 'Your wishlist is empty' : 'Your cart is empty'; ?></h5>
    <p class="text-muted"><?php echo $isWishlist ? 'Browse our products and add some to your wishlist.' : 'Browse our products and add some items.'; ?></p>
    <a href="product_list.php" class="btn btn-primary w-auto mx-auto">Browse Products</a>
  </div>
<?php elseif($isWishlist): ?>
  <div class="row g-3">
    <?php foreach($items as $it): $p = $it['product']; ?>
      <div class="col-md-4 col-lg-3">
      <div class="card h-100">
        <a href="product_detail.php?id=<?php echo $p['id']; ?>" class="text-decoration-none text-reset">
          <img src="<?php echo htmlspecialchars($p['image'] ?: 'assets/img/placeholder.svg'); ?>" class="card-img-top" style="height:200px;object-fit:cover" alt="<?php echo htmlspecialchars($p['title']); ?>" onerror="this.src='assets/img/placeholder.svg'">
        </a>
        <div class="card-body d-flex flex-column">
          <h6 class="card-title text-truncate"><?php echo htmlspecialchars($p['title']); ?></h6>
          <div class="text-primary fw-bold mb-2"><?php echo formatPrice($it['price']); ?></div>
          <div class="mt-auto d-flex gap-1">
            <a href="checkout.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-primary flex-fill"><i class="bi bi-bag"></i> Order</a>
            <form method="POST" action="cart.php?wishlist=1" class="d-inline" onsubmit="return confirm('Remove from wishlist?')">
              <input type="hidden" name="_cart_csrf" value="<?php echo cartCsrfToken(); ?>">
              <input type="hidden" name="remove_wishlist" value="<?php echo $p['id']; ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
            </form>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
<?php else: ?>
  <form method="POST">
    <input type="hidden" name="_cart_csrf" value="<?php echo cartCsrfToken(); ?>">
    <div class="row g-3">
      <?php foreach($items as $it): $p = $it['product']; ?>
      <div class="col-12">
        <div class="card">
          <div class="card-body d-flex align-items-center gap-3">
            <img src="<?php echo htmlspecialchars($p['image'] ?: 'assets/img/placeholder.svg'); ?>" class="rounded" style="width:64px;height:64px;object-fit:cover;flex-shrink:0" alt="<?php echo htmlspecialchars($p['title']); ?>" onerror="this.src='assets/img/placeholder.svg'">
            <div class="flex-grow-1 min-w-0">
              <h6 class="mb-1 text-truncate"><?php echo htmlspecialchars($p['title']); ?></h6>
              <div class="text-primary fw-bold small"><?php echo formatPrice($it['unit_price']); ?></div>
            </div>
            <div class="d-flex align-items-center gap-1 flex-shrink-0">
              <input type="number" name="qty[<?php echo $p['id']; ?>]" value="<?php echo $it['qty']; ?>" min="0" class="form-control" style="width:60px">
              <div class="fw-bold text-nowrap small d-none d-sm-block"><?php echo formatPrice($it['subtotal']); ?></div>
              <form method="POST" action="cart.php" class="d-inline" onsubmit="return confirm('Remove this item?')">
                <input type="hidden" name="_cart_csrf" value="<?php echo cartCsrfToken(); ?>">
                <input type="hidden" name="remove" value="<?php echo $p['id']; ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
              </form>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="card mt-3">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div>
          <strong>Total:</strong> <span class="fs-5 fw-bold text-primary"><?php echo formatPrice($total); ?></span>
        </div>
        <div class="d-flex gap-2">
          <button type="submit" name="update" class="btn btn-outline-secondary"><i class="bi bi-arrow-repeat"></i> Update</button>
          <a href="checkout.php" class="btn btn-primary"><i class="bi bi-credit-card"></i> Proceed to Checkout</a>
        </div>
      </div>
    </div>
  </form>
<?php endif; ?>
<?php require_once __DIR__.'/templates/footer.php'; ?>
