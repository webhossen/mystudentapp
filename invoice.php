<?php
require_once __DIR__.'/config/config.php';
session_start();
if(empty($_SESSION['user_id'])){ header('Location: login.php'); exit; }

$order_id = isset($_GET['order'])?intval($_GET['order']):0;
if(!$order_id) { header('Location: orders.php'); exit; }

$pdo = getPDO();
$stmt = $pdo->prepare('SELECT * FROM orders WHERE id=? AND user_id=?');
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();
if(!$order){ die('Order not found'); }

$items = $pdo->prepare('SELECT oi.quantity, oi.price, p.title FROM order_items oi JOIN products p ON oi.product_id=p.id WHERE oi.order_id=?');
$items->execute([$order_id]);
$rows = $items->fetchAll();

require_once __DIR__.'/templates/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4 no-print">
  <h2 class="fw-bold mb-0"><i class="bi bi-file-text me-2"></i>Invoice</h2>
  <div class="d-flex gap-2">
    <button class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
    <?php if(in_array($order['status'], ['paid','processing','shipped','delivered'])): ?>
      <a href="return_request.php?order=<?php echo $order['id']; ?>" class="btn btn-outline-warning"><i class="bi bi-arrow-return-left"></i> Request Return</a>
    <?php endif; ?>
    <a class="btn btn-primary" href="orders.php"><i class="bi bi-arrow-left"></i> Back to Orders</a>
  </div>
</div>

<div class="card p-4" id="invoice">
  <div class="d-flex justify-content-between align-items-start border-bottom pb-3 mb-3">
    <div>
      <h4 class="fw-bold"><?php echo APP_NAME; ?></h4>
      <p class="small text-muted mb-0">Digital Product Invoice</p>
    </div>
    <div class="text-end">
      <h5 class="fw-bold">Invoice</h5>
      <p class="small text-muted mb-0">Order #<?php echo $order['id']; ?></p>
    </div>
  </div>

  <div class="row mb-3 small">
    <div class="col-sm-6">
      <strong>Order ID:</strong> #<?php echo $order['id']; ?><br>
      <strong>Date:</strong> <?php echo date('F j, Y \a\t g:i A', strtotime($order['created_at'])); ?><br>
      <strong>Transaction:</strong> <?php echo htmlspecialchars(substr($order['transaction_id'] ?: '-', 0, 30)); ?>
    </div>
    <div class="col-sm-6 text-sm-end">
      <strong>Customer:</strong> <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'N/A'); ?><br>
      <strong>Status:</strong> <span class="fw-bold text-<?php echo $order['status'] === 'paid' ? 'success' : 'warning'; ?>"><?php echo ucfirst($order['status']); ?></span>
    </div>
  </div>

  <table class="table">
    <thead class="table-light">
      <tr><th>Product</th><th>Qty</th><th>Price</th></tr>
    </thead>
    <tbody>
      <?php foreach($rows as $r): ?>
        <tr>
          <td><?php echo htmlspecialchars($r['title']); ?></td>
          <td><?php echo $r['quantity']; ?></td>
          <td><?php echo formatPrice($r['price']); ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <div class="text-end">
    <strong class="fs-5 text-primary">Total: <?php echo formatPrice($order['total']); ?></strong>
  </div>
  <div class="border-top pt-3 mt-3 text-center small text-muted">
    Thank you for your purchase! For support, visit <?php echo APP_NAME; ?>
  </div>
</div>

<style>
@media print {
  .no-print { display: none !important; }
  .bottom-nav, .appbar, footer { display: none !important; }
  body { background: #fff !important; padding: 0 !important; }
}
</style>
<?php require_once __DIR__.'/templates/footer.php'; ?>
