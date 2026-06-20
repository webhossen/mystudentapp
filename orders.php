<?php
require_once __DIR__.'/config/config.php';
session_start();
if(empty($_SESSION['user_id'])){ header('Location: login.php'); exit; }

$user_id = $_SESSION['user_id'];
$pdo = getPDO();

// Handle cancel request
if(isset($_POST['cancel_order'])) {
  verifyCsrfFront();
  $orderId = (int)$_POST['cancel_order'];
  $chk = $pdo->prepare('SELECT id FROM orders WHERE id=? AND user_id=? AND status="pending"');
  $chk->execute([$orderId, $user_id]);
  if($chk->fetch()) {
    $pdo->prepare('UPDATE orders SET status="cancelled" WHERE id=?')->execute([$orderId]);
    try {
      $pdo->prepare('INSERT INTO order_tracking (order_id, status, note, created_at) VALUES (?, "cancelled", "Cancelled by customer", NOW())')->execute([$orderId]);
    } catch(Exception $e) {}
  }
  header('Location: orders.php');
  exit;
}

$stmt = $pdo->prepare('SELECT * FROM orders WHERE user_id=? ORDER BY created_at DESC');
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();

// Fetch return statuses for user's orders
$returnStatuses = [];
try {
  $retStmt = $pdo->prepare("SELECT order_id, GROUP_CONCAT(DISTINCT status ORDER BY status SEPARATOR ',') AS statuses,
    COUNT(*) AS return_count FROM product_returns WHERE user_id=? GROUP BY order_id");
  $retStmt->execute([$user_id]);
  foreach($retStmt->fetchAll() as $rs){
    $returnStatuses[$rs['order_id']] = $rs;
  }
} catch(Exception $e) {}

$returnableStatuses = ['paid', 'processing', 'shipped', 'delivered'];

require_once __DIR__.'/templates/header.php';

function getTrackingProgress($status) {
  $steps = ['pending' => 0, 'paid' => 1, 'processing' => 1, 'shipped' => 2, 'delivered' => 3];
  return $steps[$status] ?? -1;
}
function getTrackingLabel($status) {
  $labels = ['pending' => 'Order Placed', 'paid' => 'Processing', 'processing' => 'Processing', 'shipped' => 'Shipped', 'delivered' => 'Delivered'];
  return $labels[$status] ?? ucfirst($status);
}
$trackingSteps = ['pending', 'processing', 'shipped', 'delivered'];
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
  <div>
    <h2 class="fw-bold mb-0"><i class="bi bi-box me-2"></i>Your Orders</h2>
    <p class="text-muted small mb-0">Track and manage your purchases</p>
  </div>
  <a href="product_list.php" class="btn btn-sm" style="background:var(--primary);color:#fff;border:none;padding:0.5rem 1.2rem;border-radius:var(--radius-full);font-weight:600;text-decoration:none"><i class="bi bi-grid me-1"></i> Browse Products</a>
</div>

<?php if(!$orders): ?>
  <div class="card p-5 text-center border-0 shadow-sm">
    <i class="bi bi-inbox" style="font-size:3rem;color:#94a3b8"></i>
    <h5 class="mt-3">No orders yet</h5>
    <p class="text-muted">When you purchase products, they'll appear here.</p>
  </div>
<?php else: ?>
  <?php foreach($orders as $o):
    $progress = getTrackingProgress($o['status']);
    $isActive = !in_array($o['status'], ['cancelled', 'refunded']);
    $itemsStmt = $pdo->prepare('SELECT oi.*, p.title, p.image, p.file_path FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id=?');
    $itemsStmt->execute([$o['id']]);
    $items = $itemsStmt->fetchAll();
  ?>
    <div class="card mb-4 order-card border-0 shadow-sm">
      <div class="card-body p-4">
        <!-- Order Header -->
        <div class="d-flex flex-wrap justify-content-between align-items-start mb-3">
          <div>
            <h5 class="fw-bold mb-1 d-flex flex-wrap align-items-center gap-2">
              <span>Order #<?php echo $o['id']; ?></span>
              <span class="status-pill <?php echo strtolower($o['status']); ?>"><?php echo htmlspecialchars(getTrackingLabel($o['status'])); ?></span>
              <?php
                $retInfo = $returnStatuses[$o['id']] ?? null;
                if($retInfo):
                  $retBadge = '';
                  $retStatuses = explode(',', $retInfo['statuses']);
                  if(in_array('pending', $retStatuses)) $retBadge = 'pending';
                  elseif(in_array('approved', $retStatuses)) $retBadge = 'approved';
                  elseif(in_array('refunded', $retStatuses)) $retBadge = 'refunded';
                  elseif(in_array('rejected', $retStatuses)) $retBadge = 'rejected';
                  if($retBadge):
              ?>
                <span class="status-pill <?php echo $retBadge; ?>"><i class="bi bi-arrow-return-left"></i> Return: <?php echo ucfirst($retBadge); ?></span>
              <?php endif; endif; ?>
              <?php if($o['shipping_carrier']): ?>
                <span class="badge bg-light text-dark fw-normal"><i class="bi bi-truck"></i> <?php echo htmlspecialchars($o['shipping_carrier']); ?></span>
              <?php endif; ?>
            </h5>
            <div class="small text-muted"><?php echo date('F j, Y \a\t g:i A', strtotime($o['created_at'])); ?></div>
          </div>
          <div class="d-flex gap-2 mt-2 mt-sm-0">
            <a class="btn btn-sm btn-outline-primary" href="invoice.php?order=<?php echo $o['id']; ?>"><i class="bi bi-file-text"></i> Invoice</a>
            <?php if($o['status'] === 'pending'): ?>
              <form method="post" onsubmit="return confirm('Cancel this order?')" style="display:inline">
                <?php echo csrfFieldFront(); ?>
                <button type="submit" name="cancel_order" value="<?php echo $o['id']; ?>" class="btn btn-sm btn-outline-danger"><i class="bi bi-x-circle"></i> Cancel</button>
              </form>
            <?php endif; ?>
            <?php if(in_array($o['status'], $returnableStatuses)): ?>
              <a href="return_request.php?order=<?php echo $o['id']; ?>" class="btn btn-sm btn-outline-warning"><i class="bi bi-arrow-return-left"></i> Return</a>
            <?php endif; ?>
          </div>
        </div>

        <?php if(!$isActive): ?>
          <div class="alert alert-<?php echo $o['status'] === 'cancelled' ? 'danger' : 'warning'; ?> py-2 mb-3 small">
            <i class="bi bi-<?php echo $o['status'] === 'cancelled' ? 'x-circle' : 'arrow-counterclockwise'; ?> me-1"></i>
            This order has been <?php echo $o['status']; ?>.
          </div>
        <?php else: ?>
          <!-- Tracking Progress Bar -->
          <div class="tracking-progress mb-4">
            <div class="progress-track">
              <div class="progress-bg"></div>
              <div class="progress-fill" style="width:<?php echo $progress > 0 ? round($progress / 3 * 100) : 0; ?>%"></div>
              <?php foreach($trackingSteps as $i => $step): ?>
                <div class="progress-step <?php echo $progress >= $i ? 'completed' : ''; ?> <?php echo $step === $o['status'] ? 'current' : ''; ?>">
                  <div class="step-dot">
                    <?php if($progress > $i): ?>
                      <i class="bi bi-check-lg"></i>
                    <?php elseif($step === $o['status']): ?>
                      <i class="bi bi-circle-fill"></i>
                    <?php else: ?>
                      <i class="bi bi-circle"></i>
                    <?php endif; ?>
                  </div>
                  <span class="step-label"><?php echo getTrackingLabel($step); ?></span>
                  <?php
                    $dateStr = '';
                    if($step === 'shipped' && $o['shipped_at']) $dateStr = date('M j', strtotime($o['shipped_at']));
                    elseif($step === 'delivered' && $o['delivered_at']) $dateStr = date('M j', strtotime($o['delivered_at']));
                    elseif($step === 'pending') $dateStr = date('M j', strtotime($o['created_at']));
                  ?>
                  <?php if($dateStr): ?>
                    <span class="step-date"><?php echo $dateStr; ?></span>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Tracking Number & Shipping -->
          <?php if($o['tracking_number']): ?>
            <div class="tracking-info mb-3">
              <i class="bi bi-truck text-primary me-1"></i>
              <strong>Tracking:</strong>
              <span class="tracking-number"><?php echo htmlspecialchars($o['tracking_number']); ?></span>
              <?php if($o['shipping_carrier']): ?>
                <span class="text-muted small ms-1">via <?php echo htmlspecialchars($o['shipping_carrier']); ?></span>
              <?php endif; ?>
            </div>
          <?php endif; ?>

          <?php if($o['shipping_address']): ?>
            <div class="small text-muted mb-3">
              <i class="bi bi-geo-alt me-1"></i>
              <?php echo nl2br(htmlspecialchars($o['shipping_address'])); ?>
            </div>
          <?php endif; ?>

        <?php endif; ?>

        <!-- Order Items Table -->
        <div class="table-responsive">
          <table class="table table-borderless mb-0 align-middle">
            <thead class="table-light">
              <tr><th>Product</th><th>Qty</th><th>Price</th></tr>
            </thead>
            <tbody>
              <?php foreach($items as $r): ?>
                <tr>
                  <td>
                    <div class="d-flex align-items-center gap-2">
                      <img src="<?php echo htmlspecialchars($r['image'] ?: 'assets/img/placeholder.svg'); ?>" style="width:40px;height:40px;object-fit:cover;border-radius:6px" onerror="this.src='assets/img/placeholder.svg'">
                      <span class="fw-medium"><?php echo htmlspecialchars($r['title'] ?? 'Product'); ?></span>
                    </div>
                  </td>
                  <td><?php echo $r['quantity']; ?></td>
                  <td><?php echo formatPrice($r['price']); ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if(!$items): ?>
                <tr><td colspan="3" class="text-muted small text-center py-2">No items found for this order.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Order Footer -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 pt-2 border-top">
          <div class="small text-muted">
            <?php if($o['payment_method']): ?>
              <span class="me-3"><i class="bi bi-credit-card me-1"></i> <?php echo htmlspecialchars(ucfirst($o['payment_method'])); ?></span>
            <?php endif; ?>
            <?php if($o['transaction_id']): ?>
              <span>TX: <span class="font-monospace"><?php echo htmlspecialchars(substr($o['transaction_id'], 0, 20)); ?></span></span>
            <?php endif; ?>
          </div>
          <div class="fw-bold fs-5 mt-2 mt-sm-0">Total: <?php echo formatPrice($o['total']); ?></div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
<?php endif; ?>
<?php require_once __DIR__.'/templates/footer.php'; ?>