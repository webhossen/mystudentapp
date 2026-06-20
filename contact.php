<?php
require_once __DIR__.'/config/config.php';
session_start();
$user_email = '';
if(!empty($_SESSION['user_id'])){
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT email FROM users WHERE id=?');
    $stmt->execute([$_SESSION['user_id']]);
    $user_email = $stmt->fetchColumn() ?: '';
}
require_once __DIR__.'/templates/header.php';
?>
<div class="d-flex align-items-center gap-3 mb-4">
  <div class="bg-primary-soft rounded-circle p-3 d-flex align-items-center justify-content-center" style="width:56px;height:56px;background:var(--primary-soft)">
    <i class="bi bi-envelope text-primary fs-3"></i>
  </div>
  <div>
    <h2 class="fw-bold mb-0">Contact Support</h2>
    <p class="text-muted small mb-0">Have a question or issue? We're here to help.</p>
  </div>
</div>
<?php if(!empty($_SESSION['support_flash'])): ?>
  <div class="alert alert-info alert-dismissible fade show"><?php echo htmlspecialchars($_SESSION['support_flash']); unset($_SESSION['support_flash']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<div class="row justify-content-center">
  <div class="col-md-8 col-lg-6">
    <div class="card">
      <div class="card-body p-4">
        <form method="POST" action="support_submit.php">
          <?php echo csrfFieldFront(); ?>
          <div class="mb-3">
            <label class="form-label small fw-medium">Email</label>
            <input class="form-control" name="email" type="email" value="<?php echo htmlspecialchars($user_email); ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-medium">Subject</label>
            <input class="form-control" name="subject" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-medium">Message</label>
            <textarea class="form-control" name="message" rows="5" required></textarea>
          </div>
          <button class="btn btn-primary w-100"><i class="bi bi-send me-1"></i> Send Message</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__.'/templates/footer.php'; ?>
