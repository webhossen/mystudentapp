<?php
require_once __DIR__.'/config/config.php';
session_start();
if(empty($_SESSION['user_id'])){ header('Location: login.php'); exit; }

$order_id = intval($_GET['order'] ?? 0);
if(!$order_id){ header('Location: orders.php'); exit; }

$pdo = getPDO();
$stmt = $pdo->prepare('SELECT * FROM orders WHERE id=? AND user_id=?');
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();
if(!$order){ header('Location: orders.php'); exit; }

$payment_names = [
  'cod' => 'Cash on Delivery',
  'visa' => 'Visa',
];

require_once __DIR__.'/templates/header.php';
?>
<div class="card p-4 p-md-5 text-center border-0 shadow-sm">
  <div style="width:72px;height:72px;border-radius:50%;background:var(--primary-soft);display:flex;align-items:center;justify-content:center;margin:0 auto">
    <i class="bi bi-check-lg text-primary" style="font-size:2.2rem"></i>
  </div>
  <h3 class="mt-3 fw-bold">Order Submitted!</h3>
  <p class="text-muted mb-4">Your order #<?php echo $order['id']; ?> has been placed successfully.</p>

  <div class="row g-3 text-start mx-auto" style="max-width:600px">
    <div class="col-sm-6">
      <div class="card bg-light border-0 p-3">
        <small class="text-muted d-block">Order ID</small>
        <strong>#<?php echo $order['id']; ?></strong>
      </div>
    </div>
    <div class="col-sm-6">
      <div class="card bg-light border-0 p-3">
        <small class="text-muted d-block">Payment Method</small>
        <strong><?php echo $payment_names[$order['payment_method']] ?? ucfirst($order['payment_method']); ?></strong>
      </div>
    </div>
    <div class="col-sm-6">
      <div class="card bg-light border-0 p-3">
        <small class="text-muted d-block">Total</small>
        <strong class="text-primary"><?php echo formatPrice($order['total']); ?></strong>
      </div>
    </div>
    <div class="col-sm-6">
      <div class="card bg-light border-0 p-3">
        <small class="text-muted d-block">Status</small>
        <span class="status-pill pending d-inline-flex w-auto">Pending Verification</span>
      </div>
    </div>
  </div>

  <div class="alert alert-info mt-4 mx-auto text-start" style="max-width:600px">
    <div class="d-flex gap-2">
      <i class="bi bi-info-circle me-1 flex-shrink-0 mt-1"></i>
      <div>
        <strong>What happens next?</strong>
        <ol class="mb-0 mt-1 ps-3 small">
          <li>Your payment is being verified (usually within minutes during business hours).</li>
          <li>Once confirmed, your order status will change to <strong>Processing</strong>.</li>
          <li>You'll be able to download your products and track your order status in real time.</li>
        </ol>
      </div>
    </div>
  </div>

  <div class="mt-3 d-flex gap-2 justify-content-center flex-wrap">
    <a href="orders.php" class="btn btn-primary"><i class="bi bi-box me-1"></i> Track Order</a>
    <a href="product_list.php" class="btn btn-outline-secondary"><i class="bi bi-grid me-1"></i> Continue Shopping</a>
  </div>
</div>

<!-- Tracking preview -->
<?php
$trkStmt = $pdo->prepare('SELECT * FROM order_tracking WHERE order_id=? ORDER BY created_at ASC');
$trkStmt->execute([$order_id]);
$trackingEvents = $trkStmt->fetchAll();
if($trackingEvents):
?>
  <div class="card mt-4 border-0 shadow-sm">
    <div class="card-body p-4">
      <h6 class="fw-bold mb-3"><i class="bi bi-clock-history me-2"></i>Tracking Timeline</h6>
      <div class="tracking-timeline">
        <?php foreach($trackingEvents as $t): ?>
          <div class="tracking-event">
            <div class="tracking-dot <?php echo $t['status']; ?>"></div>
            <div>
              <strong class="small d-block"><?php echo ucfirst($t['status']); ?></strong>
              <?php if($t['note']): ?><span class="small text-muted d-block"><?php echo htmlspecialchars($t['note']); ?></span><?php endif; ?>
              <span class="small text-muted"><?php echo date('M j, Y g:i A', strtotime($t['created_at'])); ?></span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php require_once __DIR__.'/templates/footer.php'; ?>