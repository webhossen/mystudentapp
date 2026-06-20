<?php
require_once __DIR__.'/config/config.php';
require_once __DIR__.'/templates/header.php';
$pdo = getPDO();
$stmt = $pdo->query('SELECT * FROM faqs ORDER BY id DESC');
$faqs = $stmt->fetchAll();
?>
<div class="d-flex align-items-center gap-3 mb-4">
  <div class="bg-primary-soft rounded-circle p-3 d-flex align-items-center justify-content-center" style="width:56px;height:56px;background:var(--primary-soft)">
    <i class="bi bi-question-circle text-primary fs-3"></i>
  </div>
  <div>
    <h2 class="fw-bold mb-0">Frequently Asked Questions</h2>
    <p class="text-muted small mb-0">Find answers to common questions about our products and services.</p>
  </div>
</div>
<?php if(!$faqs): ?>
  <div class="card p-5 text-center">
    <i class="bi bi-inbox" style="font-size:3rem;color:#94a3b8"></i>
    <h5 class="mt-3">No FAQs yet</h5>
    <p class="text-muted">Check back later or <a href="contact.php">contact us</a> with your questions.</p>
  </div>
<?php else: ?>
  <div class="accordion" id="faqs">
    <?php foreach($faqs as $f): ?>
      <div class="accordion-item shadow-sm">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq<?php echo $f['id']; ?>">
            <?php echo htmlspecialchars($f['question']); ?>
          </button>
        </h2>
        <div id="faq<?php echo $f['id']; ?>" class="accordion-collapse collapse" data-bs-parent="#faqs">
          <div class="accordion-body"><?php echo nl2br(htmlspecialchars($f['answer'])); ?></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require_once __DIR__.'/templates/footer.php'; ?>
