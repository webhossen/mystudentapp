<?php
require_once __DIR__.'/config/config.php';
session_start();
if(empty($_SESSION['user_id'])){ header('Location: login.php'); exit; }

$pdo = getPDO();
$user_id = $_SESSION['user_id'];


// Schema is managed via sql/bd_fashion.sql — no DDL on page load

// Handle updates
if($_SERVER['REQUEST_METHOD'] === 'POST'){
  verifyCsrfFront();
  $action = $_POST['action'] ?? '';
  if($action === 'profile'){
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    if(!$name || !filter_var($email, FILTER_VALIDATE_EMAIL)){
      setFlash('Invalid name or email.', 'danger');
      header('Location: profile.php'); exit;
    }
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email=? AND id!=?');
    $stmt->execute([$email, $user_id]);
    if($stmt->fetch()){
      setFlash('Email already in use.', 'danger');
      header('Location: profile.php'); exit;
    }
    $stmt = $pdo->prepare('UPDATE users SET name=?, email=? WHERE id=?');
    $stmt->execute([$name, $email, $user_id]);
    $_SESSION['user_name'] = $name;
    setFlash('Profile updated.');
    header('Location: profile.php'); exit;
  } elseif($action === 'address_add' || $action === 'address_edit'){
    $id = $action === 'address_edit' ? (int)$_POST['id'] : 0;
    $label = trim($_POST['label'] ?? 'Home');
    $address_line1 = trim($_POST['address_line1'] ?? '');
    $address_line2 = trim($_POST['address_line2'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $zip = trim($_POST['zip'] ?? '');
    $country = trim($_POST['country'] ?? 'Bangladesh');
    $type = $_POST['type'] ?? 'both';
    if(!in_array($type, ['billing','shipping','both'])) $type = 'both';
    $phone = trim($_POST['phone'] ?? '');
    if(!$address_line1 || !$city){
      setFlash('Address line 1 and city are required.', 'danger');
      header('Location: profile.php'); exit;
    }
    if($action === 'address_add'){
      $stmt = $pdo->prepare('INSERT INTO addresses (user_id, label, address_line1, address_line2, city, state, zip, country, type, phone) VALUES (?,?,?,?,?,?,?,?,?,?)');
      $stmt->execute([$user_id, $label, $address_line1, $address_line2, $city, $state, $zip, $country, $type, $phone]);
      setFlash('Address added.');
    } else {
      $stmt = $pdo->prepare('UPDATE addresses SET label=?, address_line1=?, address_line2=?, city=?, state=?, zip=?, country=?, type=?, phone=? WHERE id=? AND user_id=?');
      $stmt->execute([$label, $address_line1, $address_line2, $city, $state, $zip, $country, $type, $phone, $id, $user_id]);
      setFlash('Address updated.');
    }
    header('Location: profile.php'); exit;
  } elseif($action === 'address_delete'){
    $id = (int)$_POST['id'];
    $stmt = $pdo->prepare('DELETE FROM addresses WHERE id=? AND user_id=?');
    $stmt->execute([$id, $user_id]);
    setFlash('Address deleted.');
    header('Location: profile.php'); exit;
  } elseif($action === 'address_default'){
    $id = (int)$_POST['id'];
    $pdo->prepare('UPDATE addresses SET is_default=0 WHERE user_id=?')->execute([$user_id]);
    $pdo->prepare('UPDATE addresses SET is_default=1 WHERE id=? AND user_id=?')->execute([$id, $user_id]);
    setFlash('Default address updated.');
    header('Location: profile.php'); exit;
  } elseif($action === 'password'){
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    if(strlen($new) < 8){
      setFlash('New password must be at least 8 characters.', 'danger');
      header('Location: profile.php'); exit;
    }
    $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id=?');
    $stmt->execute([$user_id]);
    $row = $stmt->fetch();
    if(!$row || !password_verify($current, $row['password_hash'])){
      setFlash('Current password is incorrect.', 'danger');
      header('Location: profile.php'); exit;
    }
    $hash = password_hash($new, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?');
    $stmt->execute([$hash, $user_id]);
    setFlash('Password changed.');
    header('Location: profile.php'); exit;
  } elseif($action === 'avatar'){
    if(!empty($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK){
      $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
      $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
      if(!in_array($ext, $allowed)){
        setFlash('Invalid file type. Allowed: jpg, jpeg, png, gif, webp.', 'danger');
        header('Location: profile.php'); exit;
      }
      $dir = __DIR__.'/uploads/avatars';
      if(!is_dir($dir)) mkdir($dir, 0755, true);
      $name = 'avatar_'.$user_id.'_'.time().'.'.$ext;
      $path = $dir.'/'.$name;
      if(move_uploaded_file($_FILES['avatar']['tmp_name'], $path)){
        $old = $pdo->prepare('SELECT avatar FROM users WHERE id=?');
        $old->execute([$user_id]);
        $oldAvatar = $old->fetchColumn();
        $stmt = $pdo->prepare('UPDATE users SET avatar=? WHERE id=?');
        $stmt->execute(['uploads/avatars/'.$name, $user_id]);
        if($oldAvatar && file_exists(__DIR__.'/'.$oldAvatar) && $oldAvatar !== 'uploads/avatars/'.$name){
          @unlink(__DIR__.'/'.$oldAvatar);
        }
        $_SESSION['user_avatar'] = 'uploads/avatars/'.$name;
        setFlash('Profile image updated.');
      } else {
        setFlash('Failed to upload image.', 'danger');
      }
    } else {
      setFlash('No image selected.', 'danger');
    }
    header('Location: profile.php'); exit;
  } elseif($action === 'avatar_delete'){
    $old = $pdo->prepare('SELECT avatar FROM users WHERE id=?');
    $old->execute([$user_id]);
    $oldAvatar = $old->fetchColumn();
    $pdo->prepare("UPDATE users SET avatar=NULL WHERE id=?")->execute([$user_id]);
    if($oldAvatar && file_exists(__DIR__.'/'.$oldAvatar)){
      @unlink(__DIR__.'/'.$oldAvatar);
    }
    $_SESSION['user_avatar'] = null;
    setFlash('Profile image removed.');
    header('Location: profile.php'); exit;
  }
}

$stmt = $pdo->prepare('SELECT name, email, created_at, avatar FROM users WHERE id=?');
$stmt->execute([$user_id]);
$user = $stmt->fetch();
if(!$user){ header('Location: auth_logout.php'); exit; }

$addresses = $pdo->prepare('SELECT * FROM addresses WHERE user_id=? ORDER BY is_default DESC, created_at DESC');
$addresses->execute([$user_id]);
$addresses = $addresses->fetchAll();

require_once __DIR__.'/templates/header.php';
?>
<h2 class="fw-bold mb-4"><i class="bi bi-person-circle me-2"></i>Your Profile</h2>

<div class="row g-4">
  <div class="col-lg-3">
    <div class="card text-center h-100">
      <div class="card-body p-4 d-flex flex-column align-items-center justify-content-center">
        <h5 class="fw-bold mb-3 w-100 text-start"><i class="bi bi-camera me-2"></i>Profile Image</h5>
        <?php
          $avatarUrl = $user['avatar'];
          $hasAvatar = $avatarUrl && file_exists(__DIR__.'/'.$avatarUrl);
          $initial = strtoupper(substr($user['name'], 0, 1));
          $colors = ['#10b981','#3b82f6','#f59e0b','#ef4444','#8b5cf6','#ec4899','#14b8a6','#f97316'];
          $bgColor = $colors[crc32($user['name']) % count($colors)];
        ?>
        <div style="width:120px;height:120px;border-radius:50%;overflow:hidden;border:4px solid var(--border);margin-bottom:1rem;flex-shrink:0;background:<?php echo $hasAvatar ? 'transparent' : $bgColor; ?>">
          <?php if($hasAvatar): ?>
            <img src="<?php echo htmlspecialchars($avatarUrl); ?>" alt="Avatar" style="width:100%;height:100%;object-fit:cover">
          <?php else: ?>
            <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:3rem;font-weight:700"><?php echo $initial; ?></div>
          <?php endif; ?>
        </div>
        <form method="POST" enctype="multipart/form-data" class="w-100">
          <?php echo csrfFieldFront(); ?>
          <input type="hidden" name="action" value="avatar">
          <div class="mb-2">
            <input type="file" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp" class="form-control form-control-sm" required>
          </div>
          <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-upload me-1"></i>Upload</button>
        </form>
        <?php if($hasAvatar): ?>
          <form method="POST" class="mt-2 w-100" onsubmit="return confirm('Remove profile image?')">
            <?php echo csrfFieldFront(); ?>
            <input type="hidden" name="action" value="avatar_delete">
            <button type="submit" class="btn btn-outline-danger btn-sm w-100"><i class="bi bi-trash me-1"></i>Remove</button>
          </form>
        <?php endif; ?>
        <div class="small text-muted mt-2">JPG, PNG, GIF, WEBP</div>
      </div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card h-100">
      <div class="card-body p-4">
        <h5 class="fw-bold mb-3"><i class="bi bi-pencil me-2"></i>Edit Profile</h5>
        <form method="POST">
          <?php echo csrfFieldFront(); ?>
          <input type="hidden" name="action" value="profile">
          <div class="mb-3">
            <label class="form-label small fw-medium">Name</label>
            <input class="form-control" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-medium">Email</label>
            <input class="form-control" name="email" type="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
          </div>
          <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Save Changes</button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-body p-4">
        <h5 class="fw-bold mb-3"><i class="bi bi-shield-lock me-2"></i>Change Password</h5>
        <form method="POST">
          <?php echo csrfFieldFront(); ?>
          <input type="hidden" name="action" value="password">
          <div class="mb-3">
            <label class="form-label small fw-medium">Current Password</label>
            <input class="form-control" name="current_password" type="password" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-medium">New Password</label>
            <input class="form-control" name="new_password" type="password" required>
          </div>
          <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Update Password</button>
        </form>
      </div>
    </div>
  </div>
  <!-- Addresses -->
  <div class="col-12">
    <div class="card">
      <div class="card-body p-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <h5 class="fw-bold mb-0"><i class="bi bi-geo-alt me-2"></i>My Addresses</h5>
          <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addressModal" onclick="resetAddressForm()"><i class="bi bi-plus-lg"></i> Add Address</button>
        </div>
        <?php if(empty($addresses)): ?>
          <p class="text-muted mb-0">No addresses saved yet.</p>
        <?php else: ?>
          <div class="row g-3">
            <?php foreach($addresses as $addr): ?>
              <div class="col-md-6">
                <div class="border rounded p-3 position-relative <?php echo $addr['is_default'] ? 'border-primary' : ''; ?>">
                  <?php if($addr['is_default']): ?>
                    <span class="badge bg-primary position-absolute top-0 end-0 m-2">Default</span>
                  <?php endif; ?>
                  <div class="mb-1">
                    <?php if($addr['type'] === 'both'): ?>
                      <span class="badge bg-info me-1">Billing</span>
                      <span class="badge bg-success">Shipping</span>
                    <?php elseif($addr['type'] === 'billing'): ?>
                      <span class="badge bg-info">Billing</span>
                    <?php else: ?>
                      <span class="badge bg-success">Shipping</span>
                    <?php endif; ?>
                  </div>
                  <div class="fw-medium"><?php echo htmlspecialchars($addr['label']); ?></div>
                  <div class="small"><?php echo htmlspecialchars($addr['address_line1']); ?></div>
                  <?php if($addr['address_line2']): ?>
                    <div class="small"><?php echo htmlspecialchars($addr['address_line2']); ?></div>
                  <?php endif; ?>
                  <div class="small"><?php echo htmlspecialchars($addr['city']); ?><?php echo $addr['state'] ? ', '.htmlspecialchars($addr['state']) : ''; ?> <?php echo htmlspecialchars($addr['zip']); ?></div>
                  <div class="small"><?php echo htmlspecialchars($addr['country']); ?></div>
                  <?php if($addr['phone']): ?>
                    <div class="small"><i class="bi bi-telephone"></i> <?php echo htmlspecialchars($addr['phone']); ?></div>
                  <?php endif; ?>
                  <div class="mt-2 d-flex gap-1">
                    <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#addressModal"
                      data-id="<?php echo $addr['id']; ?>"
                      data-label="<?php echo htmlspecialchars($addr['label'], ENT_QUOTES); ?>"
                      data-line1="<?php echo htmlspecialchars($addr['address_line1'], ENT_QUOTES); ?>"
                      data-line2="<?php echo htmlspecialchars($addr['address_line2'], ENT_QUOTES); ?>"
                      data-city="<?php echo htmlspecialchars($addr['city'], ENT_QUOTES); ?>"
                      data-state="<?php echo htmlspecialchars($addr['state'], ENT_QUOTES); ?>"
                      data-zip="<?php echo htmlspecialchars($addr['zip'], ENT_QUOTES); ?>"
                      data-country="<?php echo htmlspecialchars($addr['country'], ENT_QUOTES); ?>"
                      data-type="<?php echo $addr['type']; ?>"
                      data-phone="<?php echo htmlspecialchars($addr['phone'] ?? '', ENT_QUOTES); ?>"
                      onclick="editAddressForm(this)">
                      <i class="bi bi-pencil"></i>
                    </button>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Delete this address?')">
                      <?php echo csrfFieldFront(); ?>
                      <input type="hidden" name="action" value="address_delete">
                      <input type="hidden" name="id" value="<?php echo $addr['id']; ?>">
                      <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                    <?php if(!$addr['is_default']): ?>
                      <form method="POST" class="d-inline">
                        <?php echo csrfFieldFront(); ?>
                        <input type="hidden" name="action" value="address_default">
                        <input type="hidden" name="id" value="<?php echo $addr['id']; ?>">
                        <button class="btn btn-sm btn-outline-primary">Set Default</button>
                      </form>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- My Returns -->
  <div class="col-12">
    <div class="card">
      <div class="card-body p-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <h5 class="fw-bold mb-0"><i class="bi bi-arrow-return-left me-2" style="color:var(--orange)"></i>My Returns</h5>
          <a href="return_request.php" class="btn btn-sm" style="background:var(--orange);color:#fff;border:none;border-radius:var(--radius-full);font-weight:600"><i class="bi bi-plus-lg me-1"></i>Request Return</a>
        </div>
        <?php
        $returns = $pdo->prepare("
          SELECT pr.*, o.id AS order_id, oi.quantity, oi.price,
                 p.title AS product_title, p.image AS product_image
          FROM product_returns pr
          JOIN orders o ON o.id = pr.order_id
          LEFT JOIN order_items oi ON oi.id = pr.order_item_id
          LEFT JOIN products p ON p.id = COALESCE(pr.product_id, oi.product_id)
          WHERE pr.user_id = ?
          ORDER BY pr.created_at DESC
        ");
        $returns->execute([$user_id]);
        $returns = $returns->fetchAll();
        ?>
        <?php if(empty($returns)): ?>
          <p class="text-muted mb-0 py-2"><i class="bi bi-inbox me-1"></i>No return requests yet.</p>
        <?php else: ?>
          <?php foreach($returns as $r):
            $statusLabel = ucfirst($r['status']);
            switch($r['status']){
              case 'pending':  $statusIcon = 'bi-clock'; break;
              case 'approved': $statusIcon = 'bi-check-circle'; break;
              case 'rejected': $statusIcon = 'bi-x-circle'; break;
              case 'refunded': $statusIcon = 'bi-cash'; break;
              default:         $statusIcon = 'bi-question-circle';
            }
          ?>
          <div class="border rounded p-3 mb-3" style="border-color:var(--border) !important">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-2">
              <div class="d-flex align-items-center gap-3">
                <?php if($r['product_image']): ?>
                  <img src="<?php echo htmlspecialchars($r['product_image']); ?>" alt="" style="width:52px;height:52px;object-fit:cover;border-radius:var(--radius-sm);border:1px solid var(--border)">
                <?php else: ?>
                  <div style="width:52px;height:52px;border-radius:var(--radius-sm);background:var(--surface-soft);display:flex;align-items:center;justify-content:center;color:var(--muted)"><i class="bi bi-box"></i></div>
                <?php endif; ?>
                <div>
                  <div class="fw-semibold"><?php echo htmlspecialchars($r['product_title'] ?? 'Product'); ?></div>
                  <div class="small text-muted">
                    Order #<?php echo $r['order_id']; ?>
                    &middot; Qty: <?php echo $r['quantity'] ?: 1; ?>
                    <?php if($r['price']): ?>&middot; <?php echo formatPrice($r['price']); ?><?php endif; ?>
                  </div>
                </div>
              </div>
              <span class="status-pill <?php echo $r['status']; ?>"><i class="bi <?php echo $statusIcon; ?> me-1"></i><?php echo $statusLabel; ?></span>
            </div>
            <div class="small mb-2">
              <span class="text-muted">Reason:</span> <?php echo htmlspecialchars($r['reason']); ?>
            </div>
            <?php if($r['admin_note']): ?>
              <div class="small mb-2 p-2 rounded" style="background:var(--primary-light)">
                <span class="fw-medium"><i class="bi bi-chat-dots me-1"></i>Staff Note:</span> <?php echo nl2br(htmlspecialchars($r['admin_note'])); ?>
              </div>
            <?php endif; ?>
            <div class="d-flex flex-wrap gap-3 small text-muted">
              <span><i class="bi bi-calendar3 me-1"></i>Requested: <?php echo date('M j, Y', strtotime($r['created_at'])); ?></span>
              <?php if($r['updated_at'] && $r['updated_at'] !== $r['created_at']): ?>
                <span><i class="bi bi-arrow-repeat me-1"></i>Updated: <?php echo date('M j, Y g:i A', strtotime($r['updated_at'])); ?></span>
              <?php endif; ?>
              <a href="orders.php" class="text-decoration-none small"><i class="bi bi-box me-1"></i>View Order</a>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-12">
    <div class="card">
      <div class="card-body p-4">
        <h5 class="fw-bold mb-3"><i class="bi bi-info-circle me-2"></i>Account Info</h5>
        <div class="row g-3">
          <div class="col-sm-4">
            <div class="small text-muted">Member Since</div>
            <div class="fw-medium"><?php echo date('M j, Y', strtotime($user['created_at'])); ?></div>
          </div>
          <div class="col-sm-4">
            <div class="small text-muted">Email</div>
            <div class="fw-medium"><?php echo htmlspecialchars($user['email']); ?></div>
          </div>
          <div class="col-sm-4">
            <div class="small text-muted">Quick Links</div>
            <div><a href="orders.php" class="btn btn-sm btn-outline-primary mt-1"><i class="bi bi-box me-1"></i>My Orders</a></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Address Modal -->
<div class="modal fade" id="addressModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <?php echo csrfFieldFront(); ?>
        <input type="hidden" name="action" id="addressAction" value="address_add">
        <input type="hidden" name="id" id="addressId" value="0">
        <div class="modal-header">
          <h5 class="modal-title" id="addressModalTitle">Add Address</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label small fw-medium">Label</label>
            <input class="form-control" name="label" id="addressLabel" value="Home" placeholder="e.g. Home, Office">
          </div>
          <div class="mb-3">
            <label class="form-label small fw-medium">Address Line 1 <span class="text-danger">*</span></label>
            <input class="form-control" name="address_line1" id="addressLine1" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-medium">Address Line 2</label>
            <input class="form-control" name="address_line2" id="addressLine2">
          </div>
          <div class="row g-2">
            <div class="col-md-6 mb-3">
              <label class="form-label small fw-medium">City <span class="text-danger">*</span></label>
              <input class="form-control" name="city" id="addressCity" required>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label small fw-medium">State</label>
              <input class="form-control" name="state" id="addressState">
            </div>
          </div>
          <div class="row g-2">
            <div class="col-md-6 mb-3">
              <label class="form-label small fw-medium">ZIP Code</label>
              <input class="form-control" name="zip" id="addressZip">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label small fw-medium">Country</label>
              <input class="form-control" name="country" id="addressCountry" value="Bangladesh">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-medium">Phone</label>
            <input class="form-control" name="phone" id="addressPhone" placeholder="e.g. +1 (555) 123-4567">
          </div>
          <div class="mb-3">
            <label class="form-label small fw-medium">Type</label>
            <select class="form-select" name="type" id="addressType">
              <option value="both">Billing &amp; Shipping</option>
              <option value="billing">Billing Only</option>
              <option value="shipping">Shipping Only</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Save Address</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function resetAddressForm() {
  document.getElementById('addressAction').value = 'address_add';
  document.getElementById('addressId').value = '0';
  document.getElementById('addressModalTitle').textContent = 'Add Address';
  document.getElementById('addressLabel').value = 'Home';
  document.getElementById('addressLine1').value = '';
  document.getElementById('addressLine2').value = '';
  document.getElementById('addressCity').value = '';
  document.getElementById('addressState').value = '';
  document.getElementById('addressZip').value = '';
  document.getElementById('addressCountry').value = 'Bangladesh';
  document.getElementById('addressPhone').value = '';
  document.getElementById('addressType').value = 'both';
}
function editAddressForm(btn) {
  var d = btn.dataset;
  document.getElementById('addressAction').value = 'address_edit';
  document.getElementById('addressId').value = d.id;
  document.getElementById('addressModalTitle').textContent = 'Edit Address';
  document.getElementById('addressLabel').value = d.label;
  document.getElementById('addressLine1').value = d.line1;
  document.getElementById('addressLine2').value = d.line2;
  document.getElementById('addressCity').value = d.city;
  document.getElementById('addressState').value = d.state;
  document.getElementById('addressZip').value = d.zip;
  document.getElementById('addressCountry').value = d.country;
  document.getElementById('addressPhone').value = d.phone || '';
  document.getElementById('addressType').value = d.type || 'both';
}
</script>
<?php require_once __DIR__.'/templates/footer.php'; ?>
