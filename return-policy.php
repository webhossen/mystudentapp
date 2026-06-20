<?php
$pageTitle = 'Return Policy';
require_once __DIR__.'/config/config.php';
require_once __DIR__.'/templates/header.php';
?>
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-lg-10">
      <div class="card border-0 shadow-sm p-4 p-lg-5">
        <h1 class="fw-bold mb-1">Return & Exchange Policy</h1>
        <p class="text-muted mb-4">Last updated: <?php echo date('F j, Y'); ?></p>

        <p>Thanks for shopping at <strong><?php echo htmlspecialchars(getSetting('store_name', APP_NAME)); ?></strong>. If you are not completely satisfied with your purchase, we are here to help. We believe in making our return process as straightforward and fair as possible.</p>

        <h4 class="fw-bold mt-4">1. Return Window</h4>
        <p>You have <strong><?php echo htmlspecialchars(getSetting('return_window_days', '30')); ?> calendar days</strong> from the date your tracking status shows as "Delivered" to initiate a return or exchange for any eligible items.</p>

        <h4 class="fw-bold mt-4">2. Item Condition Requirements</h4>
        <p>To qualify for a full refund or exchange, items must meet the following baseline conditions:</p>
        <ul>
          <li>Unworn, unwashed, unaltered, and completely undamaged.</li>
          <li>In their original product packaging with all tags, labels, and protective stickers firmly attached.</li>
          <li>Accompanied by the original packing slip, invoice, or a verifiable order number.</li>
        </ul>

        <h4 class="fw-bold mt-4">3. Non-Returnable & Final Sale Items</h4>
        <p>Certain items are excluded from our general return policy due to safety, hygiene, or clear promotional disclosures. The following categories cannot be returned or exchanged:</p>
        <ul>
          <li>Items explicitly marked as <strong>"Final Sale"</strong> or <strong>"Clearance"</strong> on the product details page.</li>
          <li>Personal care products, intimate apparel, swimwear with broken hygiene seals, and cosmetics.</li>
          <li>Customized, made-to-order, or personalized goods.</li>
          <li>Digital downloads, gift cards, or software licenses.</li>
        </ul>

        <h4 class="fw-bold mt-4">4. How to Initiate a Return</h4>
        <p>Please do not ship items back to our warehouse address without first registering your request through our system. Unauthorized packages will experience severe delays or rejection.</p>
        <ol>
          <li>Go to your <a href="orders.php"><strong>Orders page</strong></a> and find the order containing the item you wish to return.</li>
          <li>Click the <strong>"Request Return"</strong> button next to the eligible item.</li>
          <li>Select the specific item(s) you wish to return and indicate the underlying reason.</li>
          <li>Select your preferred resolution: <strong>Refund to original payment method</strong>, <strong>Exchange for a different size/color</strong>, or <strong>Store Credit</strong>.</li>
          <li>Submit your request. Our team will review and notify you within <strong><?php echo htmlspecialchars(getSetting('return_review_days', '2-3')); ?> business days</strong>.</li>
        </ol>
        <p class="mt-3">
          <a href="orders.php" class="btn btn-primary btn-lg px-4"><i class="bi bi-arrow-return-left me-2"></i>Start a Return</a>
        </p>

        <h4 class="fw-bold mt-4">5. Return Shipping Costs & Fees</h4>
        <p>We handle return freight charges based on the selected resolution below:</p>
        <ul>
          <li><strong>Exchanges & Store Credit:</strong> Return shipping is entirely <strong>FREE</strong>. We provide a prepaid shipping label at no out-of-pocket cost to you.</li>
          <li><strong>Refunds to Original Payment:</strong> A flat return shipping and handling fee of <strong><?php echo htmlspecialchars(getSetting('return_fee', '$5.99')); ?></strong> will be deducted directly from your final refund total.</li>
          <li><strong>Defective or Incorrect Items:</strong> If you received a damaged or incorrect item due to our error, return shipping is completely free, and no fees will be deducted. Please upload a clear photo when prompted.</li>
        </ul>

        <h4 class="fw-bold mt-4">6. Processing Times & Refund Tracking</h4>
        <p>Once your return package is dropped off with the designated carrier, please allow <strong><?php echo htmlspecialchars(getSetting('return_transit_days', '5-7')); ?> business days</strong> for the package to safely reach our fulfillment center.</p>
        <p>Upon arrival, our operations team will inspect the item's condition within <strong><?php echo htmlspecialchars(getSetting('return_inspection_days', '2-3')); ?> business days</strong>. Once approved, your refund will be processed immediately. Credit card issuers typically take an additional <strong><?php echo htmlspecialchars(getSetting('refund_post_days', '3-10')); ?> business days</strong> to post the funds back to your account.</p>

        <h4 class="fw-bold mt-4">7. International Returns</h4>
        <p>At this time, our automated prepaid return portal only supports domestic shipments within <strong><?php echo htmlspecialchars(getSetting('store_country', 'the United States')); ?></strong>. International customers are responsible for covering their own return shipping costs via a carrier of their choice. Please mail international returns to: <strong><?php echo nl2br(htmlspecialchars(getSetting('warehouse_address', '123 Warehouse St, Fulfillment City, FC 12345'))); ?></strong>.</p>

        <h4 class="fw-bold mt-4">8. Need Further Assistance?</h4>
        <p>If you run into any technical glitches or have explicit questions about your order's eligibility, reach out to our Customer Success Team via email at <strong><?php echo htmlspecialchars(getSetting('smtp_from_email', 'support@yourstore.com')); ?></strong> or call us at <strong><?php echo htmlspecialchars(getSetting('footer_phone', '+1 (555) 123-4567')); ?></strong>.</p>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__.'/templates/footer.php'; ?>
