<?php
require_once __DIR__.'/config/config.php';
$token = $_GET['token'] ?? ($_POST['token'] ?? '');

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    verifyCsrfFront();
    $new = $_POST['password'] ?? '';
    $token = $_POST['token'] ?? '';
    if(strlen($new) < 8){ setFlash('Password must be at least 8 characters.', 'danger'); header('Location: reset_password.php?token='.urlencode($token)); exit; }
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT email,expires_at FROM password_resets WHERE token=?');
    $stmt->execute([$token]);
    $row = $stmt->fetch();
    if(!$row || strtotime($row['expires_at']) < time()){ setFlash('Invalid or expired token.', 'danger'); header('Location: forgot_password.php'); exit; }
    $hash = password_hash($new, PASSWORD_DEFAULT);
    $upd = $pdo->prepare('UPDATE users SET password_hash=? WHERE email=?');
    $upd->execute([$hash, $row['email']]);
    $del = $pdo->prepare('DELETE FROM password_resets WHERE token=?'); $del->execute([$token]);
    setFlash('Password updated. You may now login.');
    header('Location: login.php'); exit;
}

$valid = false;
if($token){
  $pdo = getPDO();
  $stmt = $pdo->prepare('SELECT email,expires_at FROM password_resets WHERE token=?');
  $stmt->execute([$token]);
  $row = $stmt->fetch();
  if($row && strtotime($row['expires_at']) >= time()) $valid = true;
}

require_once __DIR__.'/templates/header.php';
?>
<div class="row justify-content-center">
  <div class="col-md-6 col-lg-5">
    <div class="card shadow-sm">
      <div class="card-body p-4">
        <?php if(!$valid): ?>
          <div class="text-center mb-4">
            <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width:56px;height:56px;background:var(--danger-soft,#fef2f2);font-size:1.5rem;color:var(--danger)"><i class="bi bi-exclamation-triangle"></i></div>
            <h5 class="fw-bold">Invalid Link</h5>
            <p class="text-muted small">This password reset link is invalid or has expired.</p>
          </div>
          <a href="forgot_password.php" class="btn btn-primary w-100 py-2 fw-medium"><i class="bi bi-envelope me-1"></i> Request New Link</a>
        <?php else: ?>
          <div class="text-center mb-4">
            <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width:56px;height:56px;background:var(--primary-soft);font-size:1.5rem;color:var(--primary)"><i class="bi bi-key"></i></div>
            <h4 class="fw-bold mb-1">Reset Password</h4>
            <p class="text-muted small mb-0">Enter your new password below.</p>
          </div>
          <form method="POST">
            <?php echo csrfFieldFront(); ?>
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <div class="mb-3">
              <label class="form-label small fw-medium">New Password</label>
              <input class="form-control form-control-lg" type="password" name="password" required minlength="8" placeholder="At least 8 characters">
            </div>
            <button class="btn btn-primary w-100 py-2 fw-medium"><i class="bi bi-check-lg me-1"></i> Set Password</button>
          </form>
          <div class="mt-3 text-center small">
            <a href="login.php" class="text-decoration-none"><i class="bi bi-arrow-left me-1"></i>Back to login</a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__.'/templates/footer.php'; ?>
