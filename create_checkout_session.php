<?php
require_once __DIR__.'/config/config.php';
session_start();

if(empty($_SESSION['user_id'])){ header('Location: login.php'); exit; }

$paymentGateway = getSetting('payment_gateway', PAYMENT_GATEWAY);
if($paymentGateway !== 'stripe'){
    die('Stripe not configured as payment gateway.');
}

$currency_code = strtolower(getSetting('currency_code', CURRENCY_CODE));

$cart = $_SESSION['cart'] ?? [];
if(!$cart){ header('Location: cart.php'); exit; }

$pdo = getPDO();
$line_items = [];
$total = 0;

$ids = array_keys($cart);
$ph = implode(',', array_fill(0, count($ids), '?'));
$stmt = $pdo->prepare("SELECT id, title, price, sale_price FROM products WHERE id IN ($ph) AND status=1");
$stmt->execute($ids);
$products = $stmt->fetchAll();

foreach($products as $p){
    $qty = $cart[$p['id']];
    $unitPrice = (!empty($p['sale_price']) && $p['sale_price'] > 0) ? $p['sale_price'] : $p['price'];
    $amount_paise = intval(round($unitPrice * 100));
    $total += $unitPrice * $qty;
    $line_items[] = [
        'name' => $p['title'],
        'quantity' => $qty,
        'currency' => $currency_code,
        'unit_amount' => $amount_paise
    ];
}

if(empty($line_items)){ header('Location: cart.php'); exit; }

// Apply coupon discount
$coupon_code = $_SESSION['applied_coupon'] ?? '';
$discount = 0;
if($coupon_code){
  $stmt = $pdo->prepare('SELECT * FROM coupons WHERE code=? AND (expiry IS NULL OR expiry >= CURDATE()) AND (usage_limit = 0 OR used_count < usage_limit)');
  $stmt->execute([$coupon_code]);
  $coupon = $stmt->fetch();
  if($coupon){
    if($coupon['type'] === 'percent'){
      $discount = $total * ($coupon['amount'] / 100);
      if($discount > $total) $discount = $total;
    } else {
      $discount = min($coupon['amount'], $total);
    }
  }
}

// Apply tax
$tax_percent = floatval(getSetting('tax_rate', '0'));
$total_after_discount = $total - $discount;
$tax_amount = $total_after_discount * ($tax_percent / 100);
$grand_total = $total_after_discount + $tax_amount;

// For Stripe, we need to calculate line items differently to handle coupon/tax
// We'll use a single line item with the final amount
$final_amount_paise = intval(round($grand_total * 100));
$schema = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http');
$safeHost = getSafeHost();
$success_url = $schema . '://' . $safeHost . '/BD-Fashion/payment_success.php?session_id={CHECKOUT_SESSION_ID}';
$cancel_url = $schema . '://' . $safeHost . '/BD-Fashion/payment_cancel.php';

$post = [
    'payment_method_types[]' => 'card',
    'mode' => 'payment',
    'success_url' => $success_url,
    'cancel_url' => $cancel_url,
];

// Build nested line_items params
$idx = 0;
foreach($line_items as $li){
    $post["line_items[$idx][price_data][currency]"] = $li['currency'];
    $post["line_items[$idx][price_data][unit_amount]"] = $li['unit_amount'];
    $post["line_items[$idx][price_data][product_data][name]"] = $li['name'];
    $post["line_items[$idx][quantity]"] = $li['quantity'];
    $idx++;
}

// Use DB-stored stripe secret if present
$stripe_secret = getSetting('stripe_secret_key', STRIPE_SECRET_KEY);
$ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, $stripe_secret . ':');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
$resp = curl_exec($ch);
if(curl_errno($ch)){
    error_log('Stripe cURL error: '.curl_error($ch));
    die('Payment gateway error. Please try again later.');
}
curl_close($ch);

$json = json_decode($resp, true);
if(isset($json['url'])){
    header('Location: '.$json['url']); exit;
} else {
    header('Content-Type: application/json'); echo $resp; exit;
}
