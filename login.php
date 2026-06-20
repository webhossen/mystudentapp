<?php
require_once __DIR__.'/config/config.php';
session_start();
require_once __DIR__.'/templates/header.php';
?>
<div class="row justify-content-center">
  <div class="col-md-6 col-lg-5">
    <div class="card shadow-sm">
      <div class="card-body p-4">
        <div class="text-center mb-4">
          <img src="assets/uploads/site_logo_1780504721_7719723f.png" alt="logo" style="height:50px">
          <h4 class="fw-bold mt-3">Welcome Back</h4>
          <p class="text-muted small">Sign in to your account</p>
        </div>
        <form method="POST" action="auth_login.php">
          <?php echo csrfFieldFront(); ?>
          <div class="mb-3">
            <label class="form-label small fw-medium">Email</label>
            <input type="email" name="email" class="form-control form-control-lg" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-medium">Password</label>
            <input type="password" name="password" class="form-control form-control-lg" required>
          </div>
          <button class="btn btn-primary w-100 py-2 fw-medium"><i class="bi bi-box-arrow-in-right me-1"></i> Sign In</button>
        </form>
        <div class="mt-3 text-center small">
          <a href="forgot_password.php" class="text-decoration-none">Forgot password?</a>
          <span class="mx-2 text-muted">|</span>
          <a href="signup.php" class="text-decoration-none">Create account</a>
        </div>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__.'/templates/footer.php'; ?>
