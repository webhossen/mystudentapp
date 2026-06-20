<?php
require_once __DIR__.'/config/config.php';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    verifyCsrfFront();
    $email = trim($_POST['email'] ?? '');

    if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        setFlash('Enter a valid email.', 'danger'); header('Location: forgot_password.php'); exit;
    }

    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT id, name FROM users WHERE email=?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Always show the same message to prevent user enumeration
    $msg = 'If this email is registered, you will receive a password reset link.';

    if($user){
        $token = bin2hex(random_bytes(16));
        $expires = date('Y-m-d H:i:s', time() + 3600);
        $ins = $pdo->prepare('INSERT INTO password_resets (email,token,expires_at) VALUES (?,?,?)');
        $ins->execute([$email,$token,$expires]);

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $baseUrl = (defined('BASE_URL')?BASE_URL:'');
        $resetLink = $scheme.'://'.getSafeHost().$baseUrl."/reset_password.php?token={$token}";
        require_once __DIR__.'/config/email.php';
        sendPasswordResetEmail($pdo, $email, $user['name'], $resetLink);
    }

    setFlash($msg, 'info');
    header('Location: forgot_password.php'); exit;
}

require_once __DIR__.'/templates/header.php';
?>
<div class="row justify-content-center">
  <div class="col-md-6 col-lg-5">
    <div class="card shadow-sm">
      <div class="card-body p-4">
        <div class="text-center mb-4">
          <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width:56px;height:56px;background:var(--primary-soft);font-size:1.5rem;color:var(--primary)"><i class="bi bi-shield-lock"></i></div>
          <h4 class="fw-bold mb-1">Forgot Password</h4>
          <p class="text-muted small mb-0">Enter your email to receive a reset link.</p>
        </div>
        <form method="POST">
          <?php echo csrfFieldFront(); ?>
          <div class="mb-3">
            <label class="form-label small fw-medium">Email</label>
            <input class="form-control form-control-lg" name="email" type="email" required placeholder="your@email.com">
          </div>
          <button class="btn btn-primary w-100 py-2 fw-medium"><i class="bi bi-send me-1"></i> Send Reset Link</button>
        </form>
        <div class="mt-3 text-center small">
          <a href="login.php" class="text-decoration-none"><i class="bi bi-arrow-left me-1"></i>Back to login</a>
        </div>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__.'/templates/footer.php'; ?>
