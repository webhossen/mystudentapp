<?php
require_once __DIR__.'/config/config.php';
session_start();
if(empty($_SESSION['user_id'])){ header('Location: login.php'); exit; }

$pdo = getPDO();
$user_id = (int)$_SESSION['user_id'];
$success = '';
$error = '';

$order_id = isset($_GET['order']) ? (int)$_GET['order'] : 0;

if($_SERVER['REQUEST_METHOD'] === 'POST'){
  verifyCsrfFront();
  $order_id = (int)$_POST['order_id'];
  $reason = trim($_POST['reason'] ?? '');

  if(!$order_id) $error = 'Please select an order.';
  elseif(!$reason) $error = 'Please provide a reason for the return.';
  elseif(strlen($reason) < 10) $error = 'Please provide at least 10 characters describing the reason for return.';
  else {
    $ord = $pdo->prepare('SELECT id, status, created_at FROM orders WHERE id=? AND user_id=?');
    $ord->execute([$order_id, $user_id]);
    $order = $ord->fetch();
    if(!$order) $error = 'Order not found.';
    elseif(!in_array($order['status'], ['paid', 'processing', 'shipped', 'delivered']))
      $error = 'This order is not eligible for a return.';
    else {
      $items = $pdo->prepare('SELECT oi.id, oi.product_id FROM order_items oi WHERE oi.order_id=?');
      $items->execute([$order_id]);
      $orderItems = $items->fetchAll();
      $returnItemIds = $_POST['items'] ?? [];
      if(!$returnItemIds) $error = 'Please select at least one item to return.';
      else {
        $inserted = 0;
        foreach($orderItems as $item){
          if(in_array($item['id'], $returnItemIds)){
            try {
              $pdo->prepare('INSERT INTO product_returns (order_id, order_item_id, user_id, product_id, reason, status) VALUES (?,?,?,?,?,"pending")')
                ->execute([$order_id, $item['id'], $user_id, $item['product_id'], $reason]);
              $inserted++;
            } catch(Exception $e){
              $error = 'Failed to submit return request. Please try again.';
            }
          }
        }
        if($inserted){
          $success = 'Your return request has been submitted! We will review it within '.htmlspecialchars(getSetting('return_review_days', '2-3')).' business days.';
          require_once __DIR__.'/config/email.php';
          try { sendReturnRequestEmail($pdo, $order_id, $user_id); }
          catch(Exception $e){}
        } else $error = 'No items were selected for return.';
      }
    }
  }
}

$stmt = $pdo->prepare("SELECT o.id, o.total, o.status, o.created_at,
  (SELECT COUNT(*) FROM order_items oi2 WHERE oi2.order_id=o.id) AS item_count
  FROM orders o WHERE o.user_id=? AND o.status NOT IN ('pending','cancelled','refunded')
  ORDER BY o.created_at DESC");
$stmt->execute([$user_id]);
$eligibleOrders = $stmt->fetchAll();

$pageTitle = 'Request a Return';
require_once __DIR__.'/templates/header.php';
?>
<div class="container py-4" style="max-width:720px">
  <div class="mb-4">
    <a href="orders.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Back to Orders</a>
  </div>

  <div class="card border-0 shadow-sm p-4 p-lg-5">
    <h2 class="fw-bold mb-1"><i class="bi bi-arrow-return-left me-2" style="color:var(--primary)"></i>Request a Return</h2>
    <p class="text-muted mb-4">Submit a return request for eligible items in your recent orders.</p>

    <?php if($success): ?>
      <div class="text-center py-4">
        <div style="font-size:3.5rem;color:var(--primary);margin-bottom:0.5rem"><i class="bi bi-check-circle-fill"></i></div>
        <h4 class="fw-bold mb-2">Return Request Submitted!</h4>
        <p class="text-muted mb-4"><?php echo $success; ?></p>
        <div class="d-flex gap-2 justify-content-center">
          <a href="orders.php" class="btn btn-primary"><i class="bi bi-box me-2"></i>My Orders</a>
          <a href="return-policy.php" class="btn btn-outline-secondary"><i class="bi bi-file-text me-2"></i>Return Policy</a>
        </div>
      </div>
    <?php else: ?>

    <?php if($error): ?>
      <div class="alert alert-danger d-flex align-items-center gap-2"><i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="get" class="mb-4">
      <label class="form-label fw-semibold">Select Order</label>
      <div class="d-flex gap-2">
        <select name="order" class="form-select" onchange="this.form.submit()">
          <option value="">— Choose an order —</option>
          <?php foreach($eligibleOrders as $o): ?>
            <option value="<?php echo $o['id']; ?>" <?php echo $o['id'] === $order_id ? 'selected' : ''; ?>>
              #<?php echo $o['id']; ?> — <?php echo date('M j, Y', strtotime($o['created_at'])); ?> (<?php echo formatPrice($o['total']); ?>)
            </option>
          <?php endforeach; ?>
        </select>
        <noscript><button type="submit" class="btn btn-primary">Select</button></noscript>
      </div>
      <?php if(!$eligibleOrders): ?>
        <div class="text-muted small mt-2"><i class="bi bi-info-circle me-1"></i>No eligible orders found. Only paid, shipped, or delivered orders can be returned.</div>
      <?php endif; ?>
    </form>

    <?php
    $orderItems = [];
    if($order_id):
      $ord = $pdo->prepare('SELECT id, status FROM orders WHERE id=? AND user_id=?');
      $ord->execute([$order_id, $user_id]);
      $selOrder = $ord->fetch();
      if($selOrder):
        $items = $pdo->prepare("SELECT oi.id, oi.quantity, oi.price, p.title, p.image
          FROM order_items oi LEFT JOIN products p ON oi.product_id=p.id WHERE oi.order_id=?");
        $items->execute([$order_id]);
        $orderItems = $items->fetchAll();
      endif;
    endif;
    ?>

    <?php if($orderItems): ?>
    <hr class="my-4">
    <form method="post">
      <?php echo csrfFieldFront(); ?>
      <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">

      <div class="mb-3">
        <label class="form-label fw-semibold">Select Item(s) to Return</label>
        <?php foreach($orderItems as $item): ?>
          <div class="form-check border rounded p-3 mb-2" style="background:var(--surface);transition:border-color 0.15s;cursor:pointer" onmouseover="this.style.borderColor='var(--primary)'" onmouseout="this.style.borderColor=''">
            <div class="d-flex align-items-center gap-3">
              <input class="form-check-input" type="checkbox" name="items[]" value="<?php echo $item['id']; ?>" id="item_<?php echo $item['id']; ?>" style="transform:scale(1.15);cursor:pointer">
              <label class="form-check-label d-flex align-items-center gap-3 flex-grow-1" for="item_<?php echo $item['id']; ?>" style="cursor:pointer">
                <?php if($item['image']): ?>
                  <img src="<?php echo BASE_URL; ?>/<?php echo htmlspecialchars($item['image']); ?>" style="width:48px;height:48px;object-fit:cover;border-radius:8px;border:1px solid var(--border)">
                <?php else: ?>
                  <div style="width:48px;height:48px;border-radius:8px;background:var(--surface-soft);display:flex;align-items:center;justify-content:center;color:var(--muted)"><i class="bi bi-box"></i></div>
                <?php endif; ?>
                <div>
                  <strong><?php echo htmlspecialchars($item['title'] ?? 'Product'); ?></strong>
                  <div class="small text-muted">Qty: <?php echo $item['quantity']; ?> &middot; <?php echo formatPrice($item['price']); ?></div>
                </div>
              </label>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">Reason for Return <span class="text-danger">*</span></label>
        <textarea name="reason" class="form-control" rows="3" placeholder="Please describe why you're returning this item (minimum 10 characters)..." required></textarea>
        <div class="form-text"><i class="bi bi-info-circle me-1"></i>Provide as much detail as possible to help us process your request quickly.</div>
      </div>

      <button type="submit" class="btn btn-primary btn-lg w-100"><i class="bi bi-send me-2"></i>Submit Return Request</button>
      <p class="text-muted small text-center mt-3 mb-0">By submitting, you agree to our <a href="return-policy.php">Return Policy</a>.</p>
    </form>
    <?php elseif($order_id): ?>
      <div class="alert alert-warning d-flex align-items-center gap-2 mt-3"><i class="bi bi-exclamation-triangle"></i> This order has no items or is not eligible for return.</div>
    <?php endif; ?>
    <?php endif; ?>
  </div>
</div>
<?php require_once __DIR__.'/templates/footer.php'; ?>
