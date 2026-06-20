<?php
$pageTitle = 'Offline';
require_once __DIR__.'/templates/header.php';
?>
<div class="container py-5 text-center">
  <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-4" style="width:80px;height:80px;background:var(--warning-soft,#fff3cd);font-size:2.5rem;color:var(--warning,#ffc107)">
    <i class="bi bi-wifi-off"></i>
  </div>
  <h3 class="fw-bold mb-2">You're Offline</h3>
  <p class="text-muted mb-4" style="max-width:400px;margin:0 auto">Please check your internet connection and try again. Pages you've visited recently may still be available.</p>
  <button onclick="location.reload()" class="btn btn-primary px-4 py-2"><i class="bi bi-arrow-clockwise me-1"></i> Retry</button>
</div>
<?php require_once __DIR__.'/templates/footer.php'; ?>
