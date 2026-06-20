<?php
require_once __DIR__.'/config/config.php';
require_once __DIR__.'/templates/header.php';

$u = BASE_URL;

$activePms = getSetting('payment_methods_active', '[]');
$activePms = json_decode($activePms, true) ?: [];
if (empty($activePms)) $activePms = ['cod', 'visa', 'bkash', 'nagad', 'rocket'];

$pmMeta = [
  'cod'    => ['logo' => 'cash-on-delivery.png', 'color' => '#059669'],
  'visa'   => ['logo' => 'visa.svg',             'color' => '#1A1F71'],
  'bkash'  => ['logo' => 'bkash.png',            'color' => '#E2136E'],
  'nagad'  => ['logo' => 'nagad.png',            'color' => '#F2722C'],
  'rocket' => ['logo' => 'rocket.png',           'color' => '#1B813E'],
];
$pmLabels = [
  'cod'    => 'Cash on Delivery',
  'visa'   => 'Visa',
  'bkash'  => 'bKash',
  'nagad'  => 'Nagad',
  'rocket' => 'Rocket',
];

$activeCards = '';
foreach ($activePms as $pm) {
  $meta = $pmMeta[$pm] ?? null;
  if (!$meta) continue;
  $label = $pmLabels[$pm] ?? ucfirst($pm);
  $activeCards .= <<<CARD
  <div class="col-md-4">
    <div class="card border-0 shadow-sm h-100 text-center p-4" style="background:{$meta['color']}10">
      <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width:60px;height:60px;background:{$meta['color']}20;margin:0 auto">
        <img src="{$u}/assets/img/payment/{$meta['logo']}" alt="{$label}" style="height:32px">
      </div>
      <h6 class="fw-bold mb-1">{$label}</h6>
    </div>
  </div>
CARD;
}

$cardMethods = ['visa'];
$codMethods = ['cod'];
$mfsMethods = ['bkash', 'nagad', 'rocket'];

$codActive = array_intersect($activePms, $codMethods);
$cardActive = array_intersect($activePms, $cardMethods);
$mfsActive = array_intersect($activePms, $mfsMethods);

$defaultContent = '<h3 class="fw-bold mb-3">In Bangladesh, the digital payment ecosystem is highly structured and overseen by the Bangladesh Bank. It is divided into three major categories: <strong>Mobile Financial Services (MFS)</strong>, <strong>Local/International Cards</strong>, and <strong>Internet Banking (EFT/NPSB)</strong>.</h3>
<p class="mb-4">To accept these methods on a website or app, merchants integrate a <strong>Payment Gateway</strong>. Below is a complete, structured breakdown of the payment landscape in Bangladesh.</p>

<hr class="my-4">

<h4 class="fw-bold mb-3">1. Accepted Payment Methods</h4>
<p>This store accepts the following payment methods:</p>
<div class="row g-3 my-3">
  '.$activeCards.'
</div>

<hr class="my-4">

<h4 class="fw-bold mb-3">2. Core Channels &amp; Local Payment Methods</h4>

<h5 class="fw-semibold mt-4">A. Mobile Financial Services (MFS)</h5>
<p>This is the most dominant digital payment channel in the country, handling the vast majority of consumer-to-merchant daily transactions.</p>
<ul>
  <li><strong>bKash</strong> &mdash; The largest and most widely used MFS in Bangladesh, powering millions of transactions daily.</li>
  <li><strong>Nagad</strong> &mdash; The second-largest MFS network, heavily used due to competitive cash-out and transaction rates.</li>
  <li><strong>Rocket</strong> &mdash; Operated by Dutch-Bangla Bank (DBBL), standard for utility and corporate bill clearings.</li>
  <li><strong>Upay, Tap, OK Wallet:</strong> Growing alternative MFS choices supported by various commercial banks.</li>
</ul>

<h5 class="fw-semibold mt-4">B. Debit &amp; Credit Cards</h5>
<p><strong>Local Schemes:</strong></p>
<ul>
  <li><strong>DBBL Nexus:</strong> An incredibly popular local proprietary card issued by Dutch-Bangla Bank with millions of active users.</li>
  <li><strong>Q-Cash:</strong> A major shared local ATM/POS network consortium utilized by dozens of domestic banks.</li>
</ul>
<p><strong>International Networks:</strong> Local banks issue these co-branded cards for both domestic and international usage:</p>
<ul>
  <li>Visa</li>
  <li>Mastercard</li>
  <li>American Express (Amex) &mdash; <em>Exclusively acquired and issued locally by The City Bank PLC.</em></li>
  <li>UnionPay</li>
</ul>

<h5 class="fw-semibold mt-4">C. Internet Banking &amp; Digital Wallets</h5>
<p>Direct bank account transfer networks facilitated by central clearings like NPSB (National Payment Switch Bangladesh). Major banks providing immediate internet account checkouts include:</p>
<ul>
  <li>Islami Bank Bangladesh (iBanking)</li>
  <li>City Bank (Citytouch)</li>
  <li>Brac Bank (Astha)</li>
  <li>Bank Asia, Mutual Trust Bank (MTB), EBL (Skybanking)</li>
</ul>

<hr class="my-4">

<h4 class="fw-bold mb-3">3. Top Payment Gateways in Bangladesh (Aggregators)</h4>
<p>Instead of connecting to every single bank and MFS separately, businesses integrate an aggregator gateway. These providers combine all the methods listed above into a single API checkout interface.</p>
<div class="table-responsive">
<table class="table table-bordered table-striped">
  <thead class="table-dark">
    <tr>
      <th>Gateway Name</th>
      <th>License Type</th>
      <th>Supported Payment Channels</th>
      <th>Key Characteristics</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><strong>SSLCommerz</strong></td>
      <td>PSO Licensed</td>
      <td>Visa, Mastercard, Amex, DBBL Nexus, Nagad, Rocket, local Internet Banking.</td>
      <td>The pioneer and largest aggregator in BD. Excellent security infrastructure, extensive plugin support (WooCommerce, Shopify), and multi-currency capabilities.</td>
    </tr>
    <tr>
      <td><strong>aamarPay</strong></td>
      <td>PSO Licensed</td>
      <td>Nagad, Rocket, Visa, Mastercard, Amex, UnionPay, Q-Cash.</td>
      <td>Renowned for quick online onboarding, low setup barriers, built-in link-invoicing features, and flat transaction rates.</td>
    </tr>
    <tr>
      <td><strong>shurjoPay</strong></td>
      <td>PSO Licensed</td>
      <td>All local credit/debit cards, MFS apps, major corporate internet banking modules.</td>
      <td>Operated by shurjoMukhi Ltd. One of the earliest authorized providers; widely used for corporate, educational, and public sector billing.</td>
    </tr>
    <tr>
      <td><strong>PortWallet</strong></td>
      <td>Tech Provider</td>
      <td>Visa, Mastercard, Nexus.</td>
      <td>Known for a premium, developer-friendly interface framework and strong real-time API integrations.</td>
    </tr>
    <tr>
      <td><strong>EPS (Easy Payment System)</strong></td>
      <td>Fintech App / Gateway</td>
      <td>Unified QR, MFS channels, local bank networks.</td>
      <td>A newer, highly automated service regulated by Bangladesh Bank aiming to unify physical retail QR layouts with e-commerce systems.</td>
    </tr>
  </tbody>
</table>
</div>

<hr class="my-4">

<h4 class="fw-bold mb-3">4. Emerging Trends: Contactless &amp; Alternative Methods</h4>
<ul>
  <li><strong>Bangla QR:</strong> The national unified QR code standard introduced by Bangladesh Bank. It allows a customer to scan a single dynamic merchant QR using <em>any</em> supported bank app or MFS app (Citytouch, Astha, etc.), removing the need for separate merchant stands.</li>
  <li><strong>Google Wallet:</strong> Local card integrations have begun trickling in. Supported banks (like Brac Bank, City Bank, and Southeast Bank) allow users to store their Visa or Mastercard credentials securely onto Android/NFC devices.</li>
  <li><strong>Cross-Border / Freelancer Channels:</strong> While standard PayPal remains unavailable natively for domestic pull requests, alternatives like <strong>Payoneer</strong>, <strong>Wise</strong>, and newer cross-border financial tools like <strong>nsave</strong> are widely used by remote workers to clear international payouts directly into local bank accounts with extra cash incentives from the government.</li>
</ul>';

$content = getSetting('payment_info_content', $defaultContent);
?>
<div class="container py-4">
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
      <h2 class="fw-bold mb-0"><i class="bi bi-credit-card me-2"></i>Payment Methods</h2>
      <p class="text-muted small mb-0">Accepted payment methods in Bangladesh</p>
    </div>
    <a href="index.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-house"></i> Home</a>
  </div>

  <div class="card border-0 shadow-sm p-4">
    <?php echo $content; ?>
  </div>
</div>
<?php require_once __DIR__.'/templates/footer.php'; ?>
