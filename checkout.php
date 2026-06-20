<?php
require_once __DIR__.'/config/config.php';
session_start();

if(empty($_SESSION['user_id'])){
  setFlash('Please login to checkout.', 'danger');
  header('Location: login.php');
  exit;
}

require_once __DIR__.'/templates/header.php';

if(isset($_GET['buy'])){
  $buy_id = intval($_GET['buy']);
  $_SESSION['cart'] = [$buy_id => 1];
}

$cart = $_SESSION['cart'] ?? [];

if(empty($cart)){
  echo '<div class="card p-5 text-center"><i class="bi bi-cart-x" style="font-size:3rem;color:#94a3b8"></i><h5 class="mt-3">Your cart is empty</h5><a href="product_list.php" class="btn btn-primary mt-2 w-auto mx-auto">Browse Products</a></div>';
  require_once __DIR__.'/templates/footer.php';
  exit;
}

$pdo = getPDO();

$iapProductId = getSetting('iap_ios_product_id', 'bd_fashion_product');
$iapSubscriptionId = getSetting('iap_ios_subscription_id', 'bd_fashion_subscription');

$stmt = $pdo->prepare('SELECT email FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$userEmail = $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT * FROM addresses WHERE user_id=? ORDER BY is_default DESC, id DESC');
$stmt->execute([$_SESSION['user_id']]);
$userAddresses = $stmt->fetchAll();

$ids = array_keys($cart);
$ph = implode(',', array_fill(0, count($ids), '?'));
$stmt = $pdo->prepare("SELECT id, title, price, sale_price FROM products WHERE id IN ($ph) AND status=1");
$stmt->execute($ids);
$items = $stmt->fetchAll();
$subtotal = 0;
foreach($items as $it) {
    $unitPrice = (!empty($it['sale_price']) && $it['sale_price'] > 0) ? $it['sale_price'] : $it['price'];
    $subtotal += $unitPrice * $cart[$it['id']];
}

$tax_percent = floatval(getSetting('tax_rate', '0'));
$tax_amount = $subtotal * ($tax_percent / 100);
$total = $subtotal + $tax_amount;

$discount = 0;
$coupon_code = $_SESSION['applied_coupon'] ?? '';


if($coupon_code){
  $stmt = $pdo->prepare('SELECT * FROM coupons WHERE code=? AND (expiry IS NULL OR expiry >= CURDATE()) AND (usage_limit = 0 OR used_count < usage_limit)');
  $stmt->execute([$coupon_code]);
  $coupon = $stmt->fetch();
  if($coupon){
    if($coupon['type'] === 'percent'){
      $discount = $subtotal * ($coupon['amount'] / 100);
      if($discount > $subtotal) $discount = $subtotal;
    } else {
      $discount = min($coupon['amount'], $subtotal);
    }
  }
}

$grand_total = $total - $discount;

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_coupon'])){
  verifyCsrfFront();
  $code = strtoupper(trim($_POST['coupon_code'] ?? ''));
  if($code){
    $stmt = $pdo->prepare('SELECT * FROM coupons WHERE code=? AND (expiry IS NULL OR expiry >= CURDATE()) AND (usage_limit = 0 OR used_count < usage_limit)');
    $stmt->execute([$code]);
    $coupon = $stmt->fetch();
    if($coupon){
      $_SESSION['applied_coupon'] = $code;
      setFlash('Coupon applied!');
    } else {
      setFlash('Invalid or expired coupon code.', 'danger');
    }
  } else {
    unset($_SESSION['applied_coupon']);
  }
  header('Location: checkout.php'); exit;
}

if(isset($_POST['remove_coupon'])){
  verifyCsrfFront();
  unset($_SESSION['applied_coupon']);
  header('Location: checkout.php'); exit;
}
?>
<div class="checkout-header">
  <h2 class="fw-bold mb-1"><i class="bi bi-credit-card me-2"></i>Checkout</h2>
  <p class="text-muted mb-0">Complete your order securely</p>
</div>

<div class="row g-4">
  <div class="col-lg-7">
    <!-- Order Summary -->
    <div class="card">
      <div class="card-body p-4">
        <h5 class="fw-bold mb-3"><i class="bi bi-receipt me-2"></i>Order Summary</h5>
        <div class="table-responsive">
          <table class="table table-borderless checkout-table">
            <thead class="table-light"><tr><th>Product</th><th>Qty</th><th class="text-end">Price</th></tr></thead>
            <tbody>
              <?php foreach($items as $it): ?>
              <tr>
                <td><?php echo htmlspecialchars($it['title']); ?></td>
                <td><?php echo $cart[$it['id']]; ?></td>
                <td class="text-end fw-medium"><?php $u = (!empty($it['sale_price']) && $it['sale_price'] > 0) ? $it['sale_price'] : $it['price']; echo formatPrice($u * $cart[$it['id']]); ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Shipping Address -->
    <div class="card mt-4">
      <div class="card-body p-4">
        <h5 class="fw-bold mb-3"><i class="bi bi-geo-alt me-2"></i>Shipping Address</h5>

        <?php if ($userAddresses): ?>
          <?php
            $hasDefaultAddr = false;
            foreach ($userAddresses as $addr) { if ($addr['is_default']) { $hasDefaultAddr = true; break; } }
          ?>
          <div class="row g-3 mb-3" id="existingAddresses">
            <?php foreach ($userAddresses as $idx => $addr): ?>
            <div class="col-md-6">
              <label class="addr-card" data-addr-id="<?php echo $addr['id']; ?>">
                <input type="radio" name="address_option" value="existing_<?php echo $addr['id']; ?>" <?php echo ($addr['is_default'] || (!$hasDefaultAddr && $idx === 0)) ? 'checked' : ''; ?>>
                <div class="addr-card-body">
                  <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-primary"><?php echo htmlspecialchars($addr['label'] ?: 'Address'); ?></span>
                    <?php if ($addr['is_default']): ?><span class="badge bg-success">Default</span><?php endif; ?>
                  </div>
                  <strong><?php echo htmlspecialchars($addr['address_line1']); ?></strong>
                  <?php if ($addr['address_line2']): ?><br><span class="text-muted"><?php echo htmlspecialchars($addr['address_line2']); ?></span><?php endif; ?>
                  <br><span class="text-muted"><?php echo htmlspecialchars($addr['city']); ?><?php echo $addr['state'] ? ', '.htmlspecialchars($addr['state']) : ''; ?> <?php echo htmlspecialchars($addr['zip']); ?></span>
                  <br><span class="text-muted"><?php echo htmlspecialchars($addr['country']); ?></span>
                  <?php if ($addr['phone']): ?><br><span class="text-muted small"><i class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($addr['phone']); ?></span><?php endif; ?>
                </div>
                <div class="addr-card-check"><i class="bi bi-check-circle-fill"></i></div>
              </label>
            </div>
            <?php endforeach; ?>
            <div class="col-md-6">
              <label class="addr-card addr-card-new" id="newAddrLabel">
                <input type="radio" name="address_option" value="new">
                <div class="addr-card-body text-center py-4">
                  <div style="font-size:2rem;color:var(--primary)"><i class="bi bi-plus-circle"></i></div>
                  <strong class="d-block mt-2">Add New Address</strong>
                  <span class="text-muted small">Enter a different shipping address</span>
                </div>
                <div class="addr-card-check"><i class="bi bi-check-circle-fill"></i></div>
              </label>
            </div>
          </div>
        <?php else: ?>
          <p class="text-muted small mb-3">No saved addresses. Please enter your shipping address below.</p>
          <!-- Hidden radio so address validation passes -->
          <input type="radio" name="address_option" value="new" checked style="display:none">
        <?php endif; ?>

        <!-- New Address Form -->
        <div id="newAddressForm" class="<?php echo $userAddresses ? 'd-none' : ''; ?>">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label small fw-medium">Label</label>
              <select name="addr_label" class="form-select">
                <option value="Home">Home</option>
                <option value="Office">Office</option>
                <option value="Other">Other</option>
              </select>
            </div>
            <div class="col-md-8">
              <label class="form-label small fw-medium">Phone</label>
              <input type="tel" name="addr_phone" class="form-control" placeholder="01XXXXXXXXX" maxlength="20">
            </div>
            <div class="col-12">
              <label class="form-label small fw-medium">Address Line 1 *</label>
              <input type="text" name="addr_line1" class="form-control" placeholder="Street address, P.O. box" required>
            </div>
            <div class="col-12">
              <label class="form-label small fw-medium">Address Line 2</label>
              <input type="text" name="addr_line2" class="form-control" placeholder="Apartment, suite, unit, building, floor">
            </div>
            <div class="col-md-5">
              <label class="form-label small fw-medium">City *</label>
              <input type="text" name="addr_city" class="form-control" placeholder="City" required>
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-medium">State</label>
              <input type="text" name="addr_state" class="form-control" placeholder="State">
            </div>
            <div class="col-md-2">
              <label class="form-label small fw-medium">ZIP</label>
              <input type="text" name="addr_zip" class="form-control" placeholder="ZIP">
            </div>
            <div class="col-md-2">
              <label class="form-label small fw-medium">Country</label>
              <input type="text" name="addr_country" class="form-control" value="Bangladesh">
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Payment Method -->
    <div class="card mt-4">
      <div class="card-body p-4">
        <h5 class="fw-bold mb-3"><i class="bi bi-wallet2 me-2"></i>Payment Method</h5>

          <?php
          $activePms = getSetting('payment_methods_active', '[]');
          $activePms = json_decode($activePms, true) ?: [];
          if (empty($activePms)) $activePms = ['cod', 'visa', 'bkash', 'nagad', 'rocket'];
          $pmMeta = [
              'cod'       => ['logo' => 'cash-on-delivery.png', 'color' => '#059669', 'txn' => false],
            'visa'      => ['logo' => 'visa.svg',      'color' => '#1A1F71', 'txn' => false],
            'iap'       => ['logo' => 'iap.svg',       'color' => '#000',    'txn' => true],
            'bkash'     => ['logo' => 'bkash.png',     'color' => '#E2136E', 'txn' => true],
            'nagad'     => ['logo' => 'nagad.png',     'color' => '#F2722C', 'txn' => true],
            'rocket'    => ['logo' => 'rocket.png',    'color' => '#1B813E', 'txn' => true],
          ];
          $pmLabels = [
            'cod'       => 'Cash on Delivery',
            'visa'      => 'Visa',
            'iap'       => 'Apple In-App',
            'bkash'     => 'bKash',
            'nagad'     => 'Nagad',
            'rocket'    => 'Rocket',
          ];
          $txnMethods = [];
          ?>

          <?php
            $cardMethods = ['visa'];
          $codMethods = ['cod'];
          $iapMethods = ['iap'];
          $mfsMethods = ['bkash', 'nagad', 'rocket'];
          $allActive = array_intersect($activePms, array_merge($cardMethods, $codMethods, $iapMethods, $mfsMethods));
          $allCod = array_intersect($allActive, $codMethods);
          $allCards = array_intersect($allActive, $cardMethods);
          $allIap = array_intersect($allActive, $iapMethods);
          $allMfs = array_intersect($allActive, $mfsMethods);
          ?>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:2.1rem">
            <?php if ($allCards): ?>
            <div class="pm-group mb-0">
              <span class="pm-group-label">Cards</span>
              <div class="row g-3">
                <?php foreach(array_values($allCards) as $pm):
                  $meta = $pmMeta[$pm] ?? null; if(!$meta) continue;
                ?>
                <div class="col-12">
                  <label class="pm-card" data-pm="<?php echo $pm; ?>">
                    <input type="radio" name="payment_method" value="<?php echo $pm; ?>" required>
                    <div class="pm-card-bg pm-card-visa">
                      <div class="pm-card-top">
                        <div class="pm-card-chip"></div>
                        <div class="pm-card-badge">Live</div>
                      </div>
                      <div class="pm-card-number">4265 **** **** 5623</div>
                      <div class="pm-card-bottom">
                        <div>
                          <div class="pm-card-label">Card Holder</div>
                          <div class="pm-card-value">MD. ANAYET HOSSEN</div>
                        </div>
                        <div class="text-end">
                          <div class="pm-card-label">Expires</div>
                          <div class="pm-card-value">12/35</div>
                        </div>
                      </div>
                      <div class="pm-card-check"><i class="bi bi-check-lg"></i></div>
                    </div>
                  </label>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endif; ?>

            <?php if ($allCod): ?>
            <div class="pm-group mb-0">
              <span class="pm-group-label">Other</span>
              <div class="row g-3">
                <?php foreach(array_values($allCod) as $pm):
                  $meta = $pmMeta[$pm] ?? null; if(!$meta) continue;
                ?>
                <div class="col-12">
                  <label class="pm-option" data-pm="<?php echo $pm; ?>">
                    <input type="radio" name="payment_method" value="<?php echo $pm; ?>" required>
                    <div class="pm-option-icon">
                      <img src="<?php echo BASE_URL; ?>/assets/img/payment/<?php echo $meta['logo']; ?>" alt="<?php echo $pmLabels[$pm] ?? $pm; ?>" style="max-width:56px;max-height:44px">
                    </div>
                    <span class="pm-option-label"><?php echo $pmLabels[$pm] ?? ucfirst($pm); ?></span>
                    <div class="pm-option-check"><i class="bi bi-check-lg"></i></div>
                  </label>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endif; ?>
          </div>

          <?php if ($allMfs): ?>
          <div class="pm-group">
            <span class="pm-group-label">Mobile Financial Services</span>
            <div class="row g-3">
              <?php foreach(array_values($allMfs) as $pm):
                $meta = $pmMeta[$pm] ?? null; if(!$meta) continue;
                $txnMethods[] = $pm;
              ?>
              <div class="col-6 col-sm-4">
                <label class="pm-option" data-pm="<?php echo $pm; ?>">
                  <input type="radio" name="payment_method" value="<?php echo $pm; ?>" required>
                  <div class="pm-option-icon">
                    <img src="<?php echo BASE_URL; ?>/assets/img/payment/<?php echo $meta['logo']; ?>" alt="<?php echo $pmLabels[$pm] ?? $pm; ?>">
                  </div>
                  <span class="pm-option-label"><?php echo $pmLabels[$pm] ?? ucfirst($pm); ?></span>
                  <div class="pm-option-check"><i class="bi bi-check-lg"></i></div>
                </label>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>

          <?php if ($allIap): ?>
          <div class="pm-group">
            <span class="pm-group-label">In-App</span>
            <div class="row g-3">
              <?php foreach(array_values($allIap) as $pm):
                $meta = $pmMeta[$pm] ?? null; if(!$meta) continue;
                $txnMethods[] = $pm;
              ?>
              <div class="col-6 col-sm-4">
                <label class="pm-option pm-option-iap d-none" data-pm="<?php echo $pm; ?>">
                  <input type="radio" name="payment_method" value="<?php echo $pm; ?>">
                  <div class="pm-option-icon"><i class="bi bi-apple"></i></div>
                  <span class="pm-option-label">Apple Pay</span>
                  <div class="pm-option-check"><i class="bi bi-check-lg"></i></div>
                </label>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>

      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card">
      <div class="card-body p-4">
        <h5 class="fw-bold mb-3"><i class="bi bi-calculator me-2"></i>Payment Details</h5>
        <div class="d-flex justify-content-between mb-2"><span class="text-muted">Subtotal</span><span><?php echo formatPrice($subtotal); ?></span></div>
        <?php if($tax_percent > 0): ?>
          <div class="d-flex justify-content-between mb-2"><span class="text-muted">Tax (<?php echo $tax_percent; ?>%)</span><span><?php echo formatPrice($tax_amount); ?></span></div>
        <?php endif; ?>
        <?php if($discount > 0): ?>
          <div class="d-flex justify-content-between mb-2 text-success"><span>Discount</span><span>-<?php echo formatPrice($discount); ?></span></div>
        <?php endif; ?>
        <hr>
        <div class="d-flex justify-content-between mb-3 grand-total"><strong>Total</strong><strong class="grand-total-amount"><?php echo formatPrice($grand_total); ?></strong></div>

        <!-- Coupon -->
        <div class="coupon-section">
          <h6 class="fw-bold mb-2"><i class="bi bi-ticket me-2"></i>Have a coupon?</h6>
          <?php if($coupon_code): ?>
            <div class="alert alert-success d-flex align-items-center justify-content-between py-2 px-3 mb-2">
              <span><i class="bi bi-check-circle me-1"></i> Coupon <strong><?php echo htmlspecialchars($coupon_code); ?></strong> applied</span>
              <form method="POST" action="checkout.php" class="d-inline">
                <?php echo csrfFieldFront(); ?>
                <button type="submit" name="remove_coupon" class="btn btn-sm btn-outline-danger ms-2"><i class="bi bi-x"></i></button>
              </form>
            </div>
          <?php else: ?>
            <form method="POST" action="checkout.php" class="coupon-form">
              <?php echo csrfFieldFront(); ?>
              <div class="input-group">
                <input class="form-control" name="coupon_code" placeholder="Enter coupon code">
                <button type="submit" name="apply_coupon" class="btn btn-outline-primary"><i class="bi bi-check"></i> Apply</button>
              </div>
            </form>
          <?php endif; ?>
        </div>

        <form method="POST" action="payment_submit.php" id="paymentForm">
          <?php echo csrfFieldFront(); ?>
          <input type="hidden" name="total" value="<?php echo round($grand_total, 2); ?>">
          <input type="hidden" name="shipping_address_id" id="shippingAddressId" value="">
          <input type="hidden" name="addr_data" id="addrData" value="">

          <button type="submit" class="btn btn-success w-100 py-2 mt-3" id="payBtn" disabled>
            <i class="bi bi-check-circle me-1"></i> Place Order
          </button>

          <!-- Card Payment Form (inline) -->
          <div id="cardPaymentSection" class="payment-expanded" style="display:none">
            <hr class="my-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-credit-card me-1"></i>Card Details</h6>
            <div class="row g-3">
              <div class="col-12">
                <div class="card-preview mb-3">
                  <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="fw-bold text-white" style="font-size:0.85rem;letter-spacing:1px">VISA</span>
                    <span class="badge bg-light text-dark rounded-pill px-2" style="font-size:0.65rem">Live</span>
                  </div>
                  <div class="mb-2 card-preview-number" id="cardDisplay">•••• •••• •••• ••••</div>
                  <div class="row g-2">
                    <div class="col">
                      <div class="card-preview-label">CARD HOLDER</div>
                      <div class="card-preview-value" id="holderDisplay">MD. ANAYET HOSSEN</div>
                    </div>
                    <div class="col-auto text-end">
                      <div class="card-preview-label">EXPIRES</div>
                      <div class="card-preview-value" id="expiryDisplay">MM/YY</div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-12">
                <label class="form-label small fw-medium">Card Number</label>
                <div class="input-group">
                  <span class="input-group-text bg-transparent"><i class="bi bi-credit-card"></i></span>
                  <input type="text" class="form-control" name="card_number" placeholder="4265 **** **** 5623" maxlength="19" inputmode="numeric" id="cardNumberInput">
                </div>
              </div>
              <div class="col-12">
                <label class="form-label small fw-medium">Card Holder</label>
                <div class="input-group">
                  <span class="input-group-text bg-transparent"><i class="bi bi-person"></i></span>
                  <input type="text" class="form-control" name="card_holder" placeholder="MD. ANAYET HOSSEN" id="cardHolderInput">
                </div>
              </div>
              <div class="col-6">
                <label class="form-label small fw-medium">Expiry Date</label>
                <div class="input-group">
                  <span class="input-group-text bg-transparent"><i class="bi bi-calendar"></i></span>
                  <input type="text" class="form-control" name="card_expiry" placeholder="MM/YY" id="cardExpiryInput">
                </div>
              </div>
              <div class="col-6">
                <label class="form-label small fw-medium">CVV</label>
                <div class="input-group">
                  <span class="input-group-text bg-transparent"><i class="bi bi-lock"></i></span>
                  <input type="text" class="form-control" name="card_cvv" placeholder="***" maxlength="4" inputmode="numeric">
                </div>
              </div>
              <div class="col-12">
                <label class="form-label small fw-medium">Email for OTP</label>
                <div class="input-group">
                  <span class="input-group-text bg-transparent"><i class="bi bi-envelope"></i></span>
                  <input type="email" class="form-control" id="otpEmail" value="<?php echo htmlspecialchars($userEmail ?: ''); ?>" placeholder="your@email.com" required>
                </div>
              </div>
            </div>

            <button type="button" class="btn btn-primary w-100 py-2 mt-3" id="sendOtpBtn" disabled>
              <i class="bi bi-send me-1"></i> Send OTP
            </button>

            <div id="otpVerifySection" style="display:none">
              <hr class="my-4">
              <h6 class="fw-bold mb-1"><i class="bi bi-shield-check me-1"></i>Verify OTP</h6>
              <p class="small text-muted mb-3">Enter the 6-digit code sent to <strong id="otpSentEmail"></strong></p>
              <div class="mb-3">
                <input type="text" class="form-control form-control-lg text-center otp-input" id="otpInput" placeholder="000000" maxlength="6" inputmode="numeric">
              </div>
              <button type="button" class="btn btn-success w-100 py-2" id="verifyOtpBtn">
                <i class="bi bi-check-circle me-1"></i> Verify &amp; Place Order
              </button>
              <div id="otpMsg" class="small mt-2 text-center"></div>
              <div class="text-center mt-2">
                <button type="button" class="btn btn-link btn-sm text-decoration-none" id="resendOtpBtn" style="display:none">Resend OTP</button>
              </div>
            </div>

            <button type="button" class="btn btn-link btn-sm w-100 mt-2 text-decoration-none text-muted back-btn">&larr; Change payment method</button>
          </div>

          <!-- MFS Payment Form (inline) -->
          <div id="mfsPaymentSection" class="payment-expanded" style="display:none">
            <hr class="my-4">
            <div class="d-flex align-items-center gap-2 mb-3" id="mfsProviderDisplay">
              <img src="<?php echo BASE_URL; ?>/assets/img/payment/bkash.png" alt="bKash" style="height:32px;border-radius:4px">
            </div>
            <h6 class="fw-bold mb-1"><i class="bi bi-phone me-1"></i>Mobile Financial Service</h6>
            <p class="small text-muted mb-3" id="mfsProviderLabel">Enter your account number to receive a verification code by E-mail.</p>

            <div id="mfsPhoneSection">
              <div class="mb-3">
                <label class="form-label small fw-medium">Account Number</label>
                <div class="input-group">
                  <span class="input-group-text bg-transparent"><i class="bi bi-phone"></i></span>
                  <input type="tel" class="form-control" id="mfsPhone" placeholder="e.g. 01XXXXXXXXX" maxlength="11" inputmode="numeric">
                </div>
                <div class="form-text small">Enter the mobile number.</div>
              </div>
              <button type="button" class="btn btn-primary w-100 py-2" id="mfsSendOtpBtn" disabled>
                <i class="bi bi-send me-1"></i> Send OTP
              </button>
              <div id="mfsPhoneMsg" class="small mt-2 text-center"></div>
              <button type="button" class="btn btn-link btn-sm w-100 mt-2 text-decoration-none text-muted back-btn">&larr; Change payment method</button>
            </div>

            <div id="mfsOtpSection" style="display:none">
              <hr class="my-4">
              <h6 class="fw-bold mb-1"><i class="bi bi-shield-check me-1"></i>Verify OTP</h6>
              <p class="small text-muted mb-3">Enter the 6-digit code sent by e-amil <strong><?php echo htmlspecialchars($userEmail ?: ''); ?></strong> For <strong id="mfsSentPhone"></strong></p>
              <div class="mb-3">
                <input type="text" class="form-control form-control-lg text-center otp-input" id="mfsOtpInput" placeholder="000000" maxlength="6" inputmode="numeric">
              </div>
              <button type="button" class="btn btn-success w-100 py-2" id="mfsVerifyOtpBtn" disabled>
                <i class="bi bi-check-circle me-1"></i> Verify &amp; Place Order
              </button>
              <div id="mfsOtpMsg" class="small mt-2 text-center"></div>
              <div class="text-center mt-2">
                <button type="button" class="btn btn-link btn-sm text-decoration-none" id="mfsResendOtpBtn" style="display:none">Resend OTP</button>
              </div>
              <button type="button" class="btn btn-link btn-sm w-100 mt-2 text-decoration-none text-muted" id="mfsOtpBackBtn">&larr; Change phone number</button>
            </div>
          </div>
        </form>

        <a href="cart.php" class="btn btn-outline-secondary w-100 mt-2"><i class="bi bi-arrow-left me-1"></i> Back to Cart</a>
      </div>
    </div>
  </div>
</div>

<script>
var cardMethods = ['visa'];
var iapMethods = ['iap'];
var mfsMethods = ['bkash', 'nagad', 'rocket'];

// Address toggle
document.querySelectorAll('input[name="address_option"]').forEach(function(el){
  el.addEventListener('change', function(){
    if (this.value === 'new') {
      document.getElementById('newAddressForm').classList.remove('d-none');
      document.getElementById('shippingAddressId').value = '';
    } else {
      document.getElementById('newAddressForm').classList.add('d-none');
      var id = this.value.replace('existing_', '');
      document.getElementById('shippingAddressId').value = id;
    }
  });
});
// Pre-select default address
(function(){
  var checked = document.querySelector('input[name="address_option"]:checked');
  if (checked) {
    var evt = new Event('change');
    checked.dispatchEvent(evt);
  }
})();

function getAddrFields(){
  var o = {};
  o.shipping_address_id = document.getElementById('shippingAddressId').value;
  o.addr_data = document.getElementById('addrData').value;
  return o;
}

function show(id){ document.getElementById(id).style.display = ''; }
function hide(id){ document.getElementById(id).style.display = 'none'; }

function getVal(id){ return document.getElementById(id).value; }
function setMsg(text, type){
  var el = document.getElementById('otpMsg');
  el.className = 'small mt-2 text-' + (type || 'danger');
  el.textContent = text;
}

// Live card preview
function updateCardPreview(){
  var num = document.getElementById('cardNumberInput').value.replace(/\D/g,'').slice(0,16);
  var display = '';
  for(var i=0;i<4;i++) display += (num.slice(i*4,i*4+4) || '••••') + (i<3?' ':'');
  document.getElementById('cardDisplay').textContent = display || '•••• •••• •••• ••••';

  var holder = document.getElementById('cardHolderInput').value.toUpperCase() || 'MD. ANAYET HOSSEN';
  document.getElementById('holderDisplay').textContent = holder;

  var exp = document.getElementById('cardExpiryInput').value || 'MM/YY';
  document.getElementById('expiryDisplay').textContent = exp;
}
document.getElementById('cardNumberInput').addEventListener('input', updateCardPreview);
document.getElementById('cardHolderInput').addEventListener('input', updateCardPreview);
document.getElementById('cardExpiryInput').addEventListener('input', updateCardPreview);

// Enable Send OTP only when all card fields are filled
function validateCardForm(){
  var num = document.getElementById('cardNumberInput').value.replace(/\D/g,'').length >= 13;
  var holder = document.getElementById('cardHolderInput').value.trim().length >= 3;
  var exp = document.getElementById('cardExpiryInput').value.replace(/\D/g,'').length >= 4;
  var cvv = document.querySelector('#cardPaymentSection input[name="card_cvv"]').value.trim().length >= 3;
  var email = document.getElementById('otpEmail').value.includes('@');
  document.getElementById('sendOtpBtn').disabled = !(num && holder && exp && cvv && email);
}
document.querySelectorAll('#cardPaymentSection input, #otpEmail').forEach(function(el){
  el.addEventListener('input', validateCardForm);
});

// Format card number with spaces
document.getElementById('cardNumberInput').addEventListener('input', function(){
  var v = this.value.replace(/\D/g,'').slice(0,16);
  this.value = v.replace(/(.{4})/g,'$1 ').trim();
});

// Auto-slash expiry
document.getElementById('cardExpiryInput').addEventListener('input', function(){
  var v = this.value.replace(/\D/g,'').slice(0,4);
  if(v.length > 2) v = v.slice(0,2) + '/' + v.slice(2);
  this.value = v;
});

// Payment method selection -> show inline forms
document.querySelectorAll('input[name="payment_method"]').forEach(function(el){
  el.addEventListener('change', function(){
    document.getElementById('payBtn').disabled = false;

    hide('cardPaymentSection');
    hide('mfsPaymentSection');

    var val = this.value;
    document.getElementById('payBtn').style.display = '';

    if (cardMethods.indexOf(val) !== -1) {
      document.getElementById('payBtn').innerHTML = '<i class="bi bi-credit-card me-1"></i> Pay with Card';
    } else if (mfsMethods.indexOf(val) !== -1) {
      var label = val.charAt(0).toUpperCase() + val.slice(1);
      document.getElementById('payBtn').innerHTML = '<i class="bi bi-phone me-1"></i> Pay with ' + label;
      showMfsProvider(val);
      resetMfsForm();
    } else if (val === 'iap') {
      document.getElementById('payBtn').innerHTML = '<i class="bi bi-apple me-1"></i> Purchase with Apple';
    } else {
      document.getElementById('payBtn').innerHTML = '<i class="bi bi-check-circle me-1"></i> Place Order';
    }
  });
});

// Back buttons (return to payment method selection)
document.querySelectorAll('.back-btn').forEach(function(btn){
  btn.addEventListener('click', function(){
    hide('cardPaymentSection');
    hide('mfsPaymentSection');
    document.getElementById('payBtn').style.display = '';
    document.querySelector('input[name="payment_method"]:checked').checked = false;
    document.getElementById('payBtn').disabled = true;
    document.getElementById('payBtn').innerHTML = '<i class="bi bi-check-circle me-1"></i> Place Order';
  });
});

document.getElementById('paymentForm').addEventListener('submit', function(e){
  var selected = document.querySelector('input[name="payment_method"]:checked');
  if (!selected) { e.preventDefault(); showToast('Please select a payment method.', 'warning'); return; }

  // Validate address
  var addrOption = document.querySelector('input[name="address_option"]:checked');
  if (!addrOption) { e.preventDefault(); showToast('Please select a shipping address.', 'warning'); return; }
  if (addrOption.value === 'new') {
    var line1 = document.querySelector('input[name="addr_line1"]').value.trim();
    var city = document.querySelector('input[name="addr_city"]').value.trim();
    if (!line1 || !city) { e.preventDefault(); showToast('Please fill in Address Line 1 and City.', 'warning'); return; }
    var addrData = {
      label: document.querySelector('select[name="addr_label"]').value,
      phone: document.querySelector('input[name="addr_phone"]').value.trim(),
      address_line1: line1,
      address_line2: document.querySelector('input[name="addr_line2"]').value.trim(),
      city: city,
      state: document.querySelector('input[name="addr_state"]').value.trim(),
      zip: document.querySelector('input[name="addr_zip"]').value.trim(),
      country: document.querySelector('input[name="addr_country"]').value.trim()
    };
    document.getElementById('addrData').value = JSON.stringify(addrData);
    document.getElementById('shippingAddressId').value = '';
  }

  if (cardMethods.indexOf(selected.value) !== -1) {
    e.preventDefault();
    document.getElementById('payBtn').style.display = 'none';
    show('cardPaymentSection');
    return;
  }
  if (mfsMethods.indexOf(selected.value) !== -1) {
    e.preventDefault();
    document.getElementById('payBtn').style.display = 'none';
    show('mfsPaymentSection');
    return;
  }
  if (iapMethods.indexOf(selected.value) !== -1) {
    e.preventDefault();
    var btn = document.getElementById('payBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Processing...';
    window.__iap.purchase(iapProductId).then(function(result){
      if (result && result.success) {
        var f = document.createElement('form');
        f.method = 'POST';
        f.action = 'payment_submit.php';
        var addrFields = getAddrFields();
        var fields = Object.assign({ payment_method: 'iap', total: '<?php echo round($grand_total, 2); ?>', transaction_id: result.transactionId || 'IAP_' + Date.now(), _csrf_token_front: '<?php echo csrfTokenFront(); ?>' }, addrFields);
        for(var k in fields){ var inp = document.createElement('input'); inp.type = 'hidden'; inp.name = k; inp.value = fields[k]; f.appendChild(inp); }
        document.body.appendChild(f);
        f.submit();
      } else {
        showToast(result && result.error ? result.error : 'Purchase failed', 'danger');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-apple me-1"></i> Purchase with Apple';
      }
    }).catch(function(err){
      showToast(err.message || 'Purchase cancelled', 'warning');
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-apple me-1"></i> Purchase with Apple';
    });
  }
});

// Card OTP flow
document.getElementById('sendOtpBtn').addEventListener('click', function(){
  var email = getVal('otpEmail');
  if(!email || !email.includes('@')){ setMsg('Please enter a valid email address.'); return; }
  var btn = this;
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Sending...';
  setMsg('');
  fetch('send_otp.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'email=' + encodeURIComponent(email)
  })
  .then(function(r){ return r.json(); })
  .then(function(data){
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-send me-1"></i> Send OTP';
    if(data.success){
      document.getElementById('otpSentEmail').textContent = email;
      hide('sendOtpBtn');
      show('otpVerifySection');
      hide('resendOtpBtn');
      setMsg('OTP sent! Check your email.', 'success');
    } else {
      setMsg(data.message || 'Failed to send OTP.');
      show('resendOtpBtn');
    }
  })
  .catch(function(){
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-send me-1"></i> Send OTP';
    setMsg('Network error.');
    show('resendOtpBtn');
  });
});

document.getElementById('resendOtpBtn').addEventListener('click', function(){
  document.getElementById('sendOtpBtn').click();
});

document.getElementById('otpInput').addEventListener('keydown', function(e){
  if(e.key === 'Enter') document.getElementById('verifyOtpBtn').click();
});

document.getElementById('verifyOtpBtn').addEventListener('click', function(){
  var otp = getVal('otpInput').trim();
  if(!otp || otp.length !== 6){ setMsg('Enter the 6-digit code.'); return; }
  var btn = this;
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Verifying...';
  setMsg('');
  fetch('verify_otp.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'otp=' + encodeURIComponent(otp)
  })
  .then(function(r){ return r.json(); })
  .then(function(data){
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Verify &amp; Confirm Order';
    if(data.success){
      setMsg('OTP verified! Placing order...', 'success');
      var email = getVal('otpEmail');
      var f = document.createElement('form');
      f.method = 'POST';
      f.action = 'payment_submit.php';
      var addrFields = getAddrFields();
      var fields = Object.assign({ payment_method: 'visa', total: '<?php echo round($grand_total, 2); ?>', transaction_id: 'OTP_' + Date.now() + '_' + Math.random().toString(36).substr(2,6), _csrf_token_front: '<?php echo csrfTokenFront(); ?>' }, addrFields);
      for(var k in fields){ var inp = document.createElement('input'); inp.type = 'hidden'; inp.name = k; inp.value = fields[k]; f.appendChild(inp); }
      document.body.appendChild(f);
      f.submit();
    } else {
      setMsg(data.message || 'Invalid OTP code.');
    }
  })
  .catch(function(){
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Verify &amp; Confirm Order';
    setMsg('Network error.');
  });
});

if(document.querySelector('input[name="payment_method"]:checked')){
  document.getElementById('payBtn').disabled = false;
}

// IAP
if (window.__iap && window.__iap.isAvailable) {
  document.querySelectorAll('.pm-option-iap').forEach(function(el){ el.classList.remove('d-none'); });
}

var iapProductId = '<?php echo $iapProductId; ?>';
var iapSubscriptionId = '<?php echo $iapSubscriptionId; ?>';

if (window.__iap) {
  window.__iap.products([iapProductId, iapSubscriptionId], function(products) {
    if (products) console.log('IAP products loaded:', products);
  });
}

// MFS helpers
function showMfsProvider(provider){
  var logos = { bkash: 'bkash.png', nagad: 'nagad.png', rocket: 'rocket.png' };
  var labels = { bkash: 'bKash', nagad: 'Nagad', rocket: 'Rocket' };
  var img = document.querySelector('#mfsProviderDisplay img');
  if (img && logos[provider]) {
    img.src = '<?php echo BASE_URL; ?>/assets/img/payment/' + logos[provider];
    img.alt = labels[provider] || provider;
  }
  var lbl = document.getElementById('mfsProviderLabel');
  if (lbl && labels[provider]) {
    lbl.textContent = 'Enter your ' + labels[provider] + ' account number to receive a verification code by E-mail.';
  }
}

function resetMfsForm(){
  document.getElementById('mfsPhone').value = '';
  document.getElementById('mfsOtpInput').value = '';
  document.getElementById('mfsSendOtpBtn').disabled = true;
  document.getElementById('mfsVerifyOtpBtn').disabled = true;
  document.getElementById('mfsPhoneSection').style.display = '';
  document.getElementById('mfsOtpSection').style.display = 'none';
  document.getElementById('mfsResendOtpBtn').style.display = 'none';
  var m = document.getElementById('mfsPhoneMsg'); m.className = 'small mt-2 text-center'; m.textContent = '';
  var m2 = document.getElementById('mfsOtpMsg'); m2.className = 'small mt-2 text-center'; m2.textContent = '';
}

document.getElementById('mfsPhone').addEventListener('input', function(){
  var ph = this.value.replace(/\D/g,'');
  this.value = ph;
  document.getElementById('mfsSendOtpBtn').disabled = ph.length < 11;
});

document.getElementById('mfsSendOtpBtn').addEventListener('click', function(){
  var phone = document.getElementById('mfsPhone').value.replace(/\D/g,'');
  var selected = document.querySelector('input[name="payment_method"]:checked');
  if (!selected || mfsMethods.indexOf(selected.value) === -1) return;
  if (phone.length < 11) { showMfsMsg('Please enter a valid 11-digit number.', 'danger'); return; }

  var btn = this;
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Sending...';
  showMfsMsg('');

  fetch('send_mfs_otp.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'phone=' + encodeURIComponent(phone) + '&provider=' + encodeURIComponent(selected.value)
  })
  .then(function(r){ return r.json(); })
  .then(function(data){
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-send me-1"></i> Send OTP';
    if (data.success) {
      document.getElementById('mfsSentPhone').textContent = phone;
      document.getElementById('mfsPhoneSection').style.display = 'none';
      document.getElementById('mfsOtpSection').style.display = '';
      document.getElementById('mfsResendOtpBtn').style.display = 'none';
      showMfsMsg('');
      showMfsOtpMsg(data.message, 'success');
    } else {
      showMfsMsg(data.message || 'Failed to send OTP.', 'danger');
      document.getElementById('mfsResendOtpBtn').style.display = '';
    }
  })
  .catch(function(){
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-send me-1"></i> Send OTP';
    showMfsMsg('Network error.', 'danger');
    document.getElementById('mfsResendOtpBtn').style.display = '';
  });
});

document.getElementById('mfsOtpInput').addEventListener('input', function(){
  this.value = this.value.replace(/\D/g,'').slice(0,6);
  document.getElementById('mfsVerifyOtpBtn').disabled = this.value.length !== 6;
});

document.getElementById('mfsOtpInput').addEventListener('keydown', function(e){
  if (e.key === 'Enter') document.getElementById('mfsVerifyOtpBtn').click();
});

document.getElementById('mfsVerifyOtpBtn').addEventListener('click', function(){
  var otp = document.getElementById('mfsOtpInput').value.trim();
  if (otp.length !== 6) return;
  var phone = document.getElementById('mfsPhone').value.replace(/\D/g,'');
  var selected = document.querySelector('input[name="payment_method"]:checked');
  if (!selected || mfsMethods.indexOf(selected.value) === -1) return;

  var btn = this;
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Verifying...';
  showMfsOtpMsg('');

  fetch('verify_mfs_otp.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'otp=' + encodeURIComponent(otp)
  })
  .then(function(r){ return r.json(); })
  .then(function(data){
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Verify &amp; Place Order';
    if (data.success) {
      showMfsOtpMsg('Payment verified! Placing order...', 'success');
      var f = document.createElement('form');
      f.method = 'POST';
      f.action = 'payment_submit.php';
      var addrFields = getAddrFields();
      var fields = Object.assign({
        payment_method: selected.value,
        mfs_phone: phone,
        transaction_id: selected.value.toUpperCase() + '_' + phone + '_' + Date.now(),
        total: '<?php echo round($grand_total, 2); ?>',
        _csrf_token_front: '<?php echo csrfTokenFront(); ?>'
      }, addrFields);
      for (var k in fields) {
        var inp = document.createElement('input');
        inp.type = 'hidden'; inp.name = k; inp.value = fields[k];
        f.appendChild(inp);
      }
      document.body.appendChild(f);
      f.submit();
    } else {
      showMfsOtpMsg(data.message || 'Invalid OTP code.', 'danger');
    }
  })
  .catch(function(){
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Verify &amp; Place Order';
    showMfsOtpMsg('Network error.', 'danger');
  });
});

document.getElementById('mfsResendOtpBtn').addEventListener('click', function(){
  document.getElementById('mfsSendOtpBtn').click();
});

document.getElementById('mfsOtpBackBtn').addEventListener('click', function(){
  document.getElementById('mfsOtpSection').style.display = 'none';
  document.getElementById('mfsPhoneSection').style.display = '';
  document.getElementById('mfsSendOtpBtn').disabled = false;
  var m = document.getElementById('mfsOtpMsg'); m.className = 'small mt-2 text-center'; m.textContent = '';
});

function showMfsMsg(text, type){
  var el = document.getElementById('mfsPhoneMsg');
  el.className = 'small mt-2 text-' + (type || 'danger');
  el.textContent = text || '';
}
function showMfsOtpMsg(text, type){
  var el = document.getElementById('mfsOtpMsg');
  el.className = 'small mt-2 text-' + (type || 'danger');
  el.textContent = text || '';
}
</script>
<?php require_once __DIR__.'/templates/footer.php'; ?>
