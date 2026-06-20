<?php
require_once __DIR__.'/config/config.php';
require_once __DIR__.'/templates/header.php';
?>
<div class="row justify-content-center">
  <div class="col-md-6 col-lg-5">
    <div class="card shadow-sm">
      <div class="card-body p-4">
        <div class="text-center mb-4">
          <img src="assets/uploads/site_logo_1780504721_7719723f.png" alt="logo" style="height:50px">
          <h4 class="fw-bold mt-3">Create Account</h4>
          <p class="text-muted small">Join us and start shopping</p>
        </div>
        <form method="POST" action="auth_register.php">
          <?php echo csrfFieldFront(); ?>
          <div class="mb-3">
            <label class="form-label small fw-medium">Name</label>
            <input type="text" name="name" class="form-control form-control-lg" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-medium">Email</label>
            <input type="email" name="email" class="form-control form-control-lg" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-medium">Password (min 8 chars)</label>
            <input type="password" name="password" class="form-control form-control-lg" minlength="8" required>
          </div>
          <button class="btn btn-primary w-100 py-2 fw-medium"><i class="bi bi-person-plus me-1"></i> Create Account</button>
        </form>
        <div class="mt-3 text-center small">
          Already have an account? <a href="login.php" class="text-decoration-none">Sign in</a>
        </div>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__.'/templates/footer.php'; ?>
