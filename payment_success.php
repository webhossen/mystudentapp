<?php
require_once __DIR__.'/config/config.php';
session_start();

$session_id = $_GET['session_id'] ?? '';
if(!$session_id){ header('Location: index.php'); exit; }

// verify session with Stripe
$ch = curl_init('https://api.stripe.com/v1/checkout/sessions/'.urlencode($session_id));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, getSetting('stripe_secret_key', STRIPE_SECRET_KEY) . ':');
curl_setopt($ch, CURLOPT_HTTPGET, true);
$resp = curl_exec($ch);
if(curl_errno($ch)){ die('Curl error: '.curl_error($ch)); }
curl_close($ch);
$json = json_decode($resp, true);

$paid = ($json['payment_status'] ?? '') === 'paid';

require_once __DIR__.'/templates/header.php';
if(!$paid){
    echo '<div class="alert alert-warning">Payment not confirmed yet.</div>';
    echo '<a href="index.php" class="btn btn-secondary">Home</a>';
    require_once __DIR__.'/templates/footer.php';
    exit;
}

// create order if not exists
$pdo = getPDO();
$payment_intent = $json['payment_intent'] ?? null;
$txn = $payment_intent ?? $session_id;

$stmt = $pdo->prepare('SELECT id FROM orders WHERE transaction_id=?');
$stmt->execute([$txn]);
$exists = $stmt->fetch();
if(!$exists){
    $cart = $_SESSION['cart'] ?? [];
    $total = 0;
    foreach($cart as $pid=>$qty){
        $p = $pdo->prepare('SELECT price, sale_price FROM products WHERE id=?'); $p->execute([$pid]); $row=$p->fetch(); if(!$row) continue;
        $unitPrice = (!empty($row['sale_price']) && $row['sale_price'] > 0) ? $row['sale_price'] : $row['price'];
        $total += $unitPrice * $qty;
    }

    // Apply coupon discount
    $coupon_code = $_SESSION['applied_coupon'] ?? '';
    $discount = 0;
    if($coupon_code){
      $stmt = $pdo->prepare('SELECT * FROM coupons WHERE code=?');
      $stmt->execute([$coupon_code]);
      $coupon = $stmt->fetch();
      if($coupon){
        if($coupon['type'] === 'percent'){
          $discount = $total * ($coupon['amount'] / 100);
          if($discount > $total) $discount = $total;
        } else {
          $discount = min($coupon['amount'], $total);
        }
        // Increment usage count
        $upd = $pdo->prepare('UPDATE coupons SET used_count = used_count + 1 WHERE code=?');
        $upd->execute([$coupon_code]);
      }
    }

    $tax_percent = floatval(getSetting('tax_rate', '0'));
    $total_after_discount = $total - $discount;
    $tax_amount = $total_after_discount * ($tax_percent / 100);
    $final_total = $total_after_discount + $tax_amount;

    $user_id = $_SESSION['user_id'] ?? null;
    $ins = $pdo->prepare('INSERT INTO orders (user_id,total,status,transaction_id,payment_method) VALUES (?,?,?,?,?)');
    $ins->execute([$user_id, $final_total, 'paid', $txn, 'stripe']);
    $order_id = $pdo->lastInsertId();
    foreach($cart as $pid=>$qty){
        $p = $pdo->prepare('SELECT price, sale_price FROM products WHERE id=?'); $p->execute([$pid]); $row=$p->fetch(); if(!$row) continue;
        $unitPrice = (!empty($row['sale_price']) && $row['sale_price'] > 0) ? $row['sale_price'] : $row['price'];
        $ins2 = $pdo->prepare('INSERT INTO order_items (order_id,product_id,quantity,price) VALUES (?,?,?,?)');
        $ins2->execute([$order_id,$pid,$qty,$unitPrice]);
    }
    // Reduce product quantities
    $qtyUpd = $pdo->prepare('UPDATE products SET quantity = GREATEST(quantity - ?, 0) WHERE id = ?');
    foreach($cart as $pid=>$qty){
        $qtyUpd->execute([$qty, $pid]);
    }
    // Send order confirmation and invoice emails
    require_once __DIR__.'/config/email.php';
    sendOrderConfirmationEmail($pdo, $order_id, $user_id, 'Stripe', $total, $tax_amount, $final_total);
    sendInvoiceEmail($pdo, $order_id, $user_id);

    // clear cart and coupon
    unset($_SESSION['cart']);
    unset($_SESSION['applied_coupon']);
}

echo '<div class="card p-5 text-center"><i class="bi bi-check-circle-fill text-success" style="font-size:4rem"></i>';
echo '<h3 class="mt-3 fw-bold">Payment Successful!</h3>';
echo '<p class="text-muted">Your order has been created. You can now download your products.</p>';
echo '<a href="orders.php" class="btn btn-primary mt-3"><i class="bi bi-box me-1"></i> View Orders</a>';
echo '<a href="product_list.php" class="btn btn-outline-secondary mt-2 ms-2">Continue Shopping</a></div>';

require_once __DIR__.'/templates/footer.php';
