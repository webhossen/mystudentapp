<?php
require_once __DIR__.'/config/config.php';
session_start();

if(empty($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST'){
  header('Location: login.php');
  exit;
}

verifyCsrfFront();

$payment_method = $_POST['payment_method'] ?? '';
$transaction_id = trim($_POST['transaction_id'] ?? '');
$mfs_phone = trim($_POST['mfs_phone'] ?? '');
$posted_total = floatval($_POST['total'] ?? 0);
$cart = $_SESSION['cart'] ?? [];

if(empty($cart)){
  header('Location: cart.php');
  exit;
}

// MFS methods require transaction ID
$mfsMethods = ['bkash', 'nagad', 'rocket'];
$isCod = ($payment_method === 'cod');
$isIap = ($payment_method === 'iap');
$isMfs = in_array($payment_method, $mfsMethods);

// Validate payment method is configured in active list
$activePms = json_decode(getSetting('payment_methods_active', '[]'), true) ?: [];
if (empty($activePms)) $activePms = ['cod', 'visa', 'bkash', 'nagad', 'rocket'];
if(!in_array($payment_method, $activePms)){
  setFlash('This payment method is not available.', 'danger');
  header('Location: checkout.php');
  exit;
}

$pdo = getPDO();

// Calculate totals
$ids = array_keys($cart);
$ph = implode(',', array_fill(0, count($ids), '?'));
$stmt = $pdo->prepare("SELECT id, price, sale_price FROM products WHERE id IN ($ph) AND status=1");
$stmt->execute($ids);
$subtotal = 0;
foreach($stmt->fetchAll() as $it) {
    $unitPrice = (!empty($it['sale_price']) && $it['sale_price'] > 0) ? $it['sale_price'] : $it['price'];
    $subtotal += $unitPrice * $cart[$it['id']];
}

$tax_percent = floatval(getSetting('tax_rate', '0'));
$tax_amount = $subtotal * ($tax_percent / 100);
$total = $subtotal + $tax_amount;

// Apply coupon
$coupon_code = $_SESSION['applied_coupon'] ?? '';
$discount = 0;
if($coupon_code){
  $stmt = $pdo->prepare('SELECT * FROM coupons WHERE code=? AND (expiry IS NULL OR expiry >= CURDATE()) AND (usage_limit = 0 OR used_count < usage_limit)');
  $stmt->execute([$coupon_code]);
  $coupon = $stmt->fetch();
  if($coupon){
    if($coupon['type'] === 'percent'){
      $discount = $subtotal * ($coupon['amount'] / 100);
    } else {
      $discount = min($coupon['amount'], $subtotal);
    }
  }
}
$grand_total = round($total - $discount, 2);

// Create order
$user_id = $_SESSION['user_id'];
$txn_id = $isCod ? ('COD_' . time() . '_' . $user_id) : ($transaction_id ?: ('MANUAL_' . time() . '_' . $user_id));
$status = 'pending';

$payment_note = '';
if ($isMfs && $mfs_phone) {
  $payment_note = 'MFS Phone: ' . $mfs_phone;
}
// Build shipping address string
$shipping_address = '';
$addr_id = intval($_POST['shipping_address_id'] ?? 0);
$addr_json = trim($_POST['addr_data'] ?? '');

if ($addr_id) {
  $stmt = $pdo->prepare('SELECT * FROM addresses WHERE id=? AND user_id=?');
  $stmt->execute([$addr_id, $user_id]);
  $addr = $stmt->fetch();
  if ($addr) {
    $parts = [];
    $parts[] = $addr['address_line1'];
    if ($addr['address_line2']) $parts[] = $addr['address_line2'];
    $cityLine = $addr['city'];
    if ($addr['state']) $cityLine .= ', '.$addr['state'];
    if ($addr['zip']) $cityLine .= ' '.$addr['zip'];
    $parts[] = $cityLine;
    $parts[] = $addr['country'];
    if ($addr['phone']) $parts[] = 'Phone: '.$addr['phone'];
    $shipping_address = implode("\n", $parts);
  }
} elseif ($addr_json) {
  $addr = json_decode($addr_json, true);
  if ($addr) {
    $parts = [];
    $parts[] = $addr['address_line1'] ?? '';
    if (!empty($addr['address_line2'])) $parts[] = $addr['address_line2'];
    $cityLine = $addr['city'] ?? '';
    if (!empty($addr['state'])) $cityLine .= ', '.$addr['state'];
    if (!empty($addr['zip'])) $cityLine .= ' '.$addr['zip'];
    $parts[] = $cityLine;
    $parts[] = $addr['country'] ?? 'Bangladesh';
    if (!empty($addr['phone'])) $parts[] = 'Phone: '.$addr['phone'];
    $shipping_address = implode("\n", $parts);

    // Save as a new address for the user
    $label = $addr['label'] ?? 'Home';
    $type = 'shipping';
    $insAddr = $pdo->prepare('INSERT INTO addresses (user_id, label, address_line1, address_line2, city, state, zip, country, type, phone) VALUES (?,?,?,?,?,?,?,?,?,?)');
    $insAddr->execute([$user_id, $label, $addr['address_line1'] ?? '', $addr['address_line2'] ?? '', $addr['city'] ?? '', $addr['state'] ?? '', $addr['zip'] ?? '', $addr['country'] ?? 'Bangladesh', $type, $addr['phone'] ?? '']);
  }
}

$ins = $pdo->prepare('INSERT INTO orders (user_id, total, status, transaction_id, payment_method, payment_note, shipping_address) VALUES (?,?,?,?,?,?,?)');
$ins->execute([$user_id, $grand_total, $status, $txn_id, $payment_method, $payment_note, $shipping_address]);
$order_id = $pdo->lastInsertId();

// Insert initial tracking event
try {
  if ($isCod) { $note = 'Order placed — Cash on Delivery'; }
  elseif ($isIap) { $note = 'Order placed — Apple In-App Purchase'; }
  elseif ($isMfs) { $note = 'Order placed — ' . ucfirst($payment_method); }
  else { $note = 'Order placed — awaiting payment verification'; }
  $trk = $pdo->prepare('INSERT INTO order_tracking (order_id, status, note) VALUES (?,?,?)');
  $trk->execute([$order_id, 'pending', $note]);
} catch(Exception $e) {}

// Save order items
foreach($cart as $pid => $qty){
  $p = $pdo->prepare('SELECT price, sale_price FROM products WHERE id=?');
  $p->execute([$pid]);
  $row = $p->fetch();
  if(!$row) continue;
  $unitPrice = (!empty($row['sale_price']) && $row['sale_price'] > 0) ? $row['sale_price'] : $row['price'];
  $ins2 = $pdo->prepare('INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?,?,?,?)');
  $ins2->execute([$order_id, $pid, $qty, $unitPrice]);
}

// Reduce product quantities
$qtyUpd = $pdo->prepare('UPDATE products SET quantity = GREATEST(quantity - ?, 0) WHERE id = ?');
foreach($cart as $pid => $qty){
  $qtyUpd->execute([$qty, $pid]);
}

// Track coupon usage
if($coupon_code && $discount > 0){
  $upd = $pdo->prepare('UPDATE coupons SET used_count = used_count + 1 WHERE code=?');
  $upd->execute([$coupon_code]);
}

// Clear cart and coupon
unset($_SESSION['cart']);
unset($_SESSION['applied_coupon']);

// Redirect to success page
header('Location: payment_submitted.php?order=' . $order_id);
exit;
