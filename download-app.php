<?php
$pageTitle = 'Download App';
require_once __DIR__.'/templates/header.php';
$apkPath = __DIR__.'/mobile-app/bd-fashion.apk';
$apkExists = file_exists($apkPath);
$apkSize = $apkExists ? round(filesize($apkPath) / 1048576, 1) : null;
$apkModified = $apkExists ? date('M d, Y H:i', filemtime($apkPath)) : null;

$appVersion = getSetting('app_version', '1.0.0');
$appVersionDate = getSetting('app_version_date', date('M d, Y'));
$appTagline = getSetting('app_tagline', 'Shop faster with offline access, push notifications, and a full-screen experience');
$featureDefaults = ['Faster Loading','Offline Access','Push Notifications','Fullscreen Mode','Auto Updates','Low Data Use','Secure HTTPS','Touch Optimized'];
$features = [];
for ($i = 1; $i <= 8; $i++) {
  $features[] = getSetting('app_feature_'.$i, $featureDefaults[$i-1]);
}
?>
<style>
.app-hero{text-align:center;padding:50px 0 30px}
.app-hero .app-icon{width:96px;height:96px;border-radius:24px;box-shadow:0 12px 40px rgba(13,110,253,.3);margin-bottom:20px;transition:transform .3s}
.app-hero .app-icon:hover{transform:scale(1.05)}
.app-hero h1{font-size:2rem;font-weight:800;letter-spacing:-.5px}
.app-hero p{color:var(--muted);max-width:520px;margin:8px auto 0;font-size:15px}
.dl-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:14px 32px;border-radius:12px;font-weight:700;font-size:15px;border:none;cursor:pointer;transition:all .25s;text-decoration:none}
.dl-btn:hover{transform:translateY(-2px);box-shadow:0 8px 25px rgba(13,110,253,.35)}.dl-btn:active{transform:translateY(0)}
.dl-btn.primary{background:var(--primary,#0d6efd);color:#fff}
.dl-btn.success{background:#059669;color:#fff}
.dl-btn.outline{background:transparent;border:2px solid var(--border,#e2e8f0);color:var(--text,#111)}
.dl-btn.outline:hover{border-color:var(--primary);color:var(--primary);box-shadow:none}
.dl-btn:disabled{opacity:.5;cursor:default;transform:none!important;box-shadow:none!important}
.platform-card{border:1px solid var(--border,#e2e8f0);border-radius:16px;padding:32px;background:var(--surface,#fff);height:100%;transition:box-shadow .25s,transform .25s;position:relative;overflow:hidden}
.platform-card:hover{box-shadow:0 12px 35px rgba(0,0,0,.08);transform:translateY(-3px)}
.platform-card .card-head{display:flex;align-items:center;gap:14px;margin-bottom:16px}
.platform-card .icon-box{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0}
.platform-card .card-head h5{font-weight:700;margin:0;font-size:1.05rem}
.platform-card .card-head .badge{font-size:10px;font-weight:600;padding:3px 8px;border-radius:6px}
.platform-card p.sub{color:var(--muted);font-size:13px;margin-bottom:16px}
.platform-card ol{margin:0;padding-left:18px;font-size:14px;color:var(--text,#334155)}
.platform-card ol li{margin-bottom:8px;line-height:1.5;padding-left:4px}
.platform-card ol li::marker{color:var(--primary);font-weight:700}
.feature-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:10px}
.feature-item{text-align:center;padding:18px 10px;border-radius:12px;background:var(--bg-muted,#f8fafc);transition:background .2s,transform .2s}
.feature-item:hover{background:var(--primary-light,#e8f4fd);transform:scale(1.04)}
.feature-item i{font-size:1.5rem;color:var(--primary,#0d6efd);margin-bottom:8px;display:block}
.feature-item span{font-size:12px;font-weight:600;color:var(--muted,#64748b);display:block}
.info-table{width:100%;font-size:13px}
.info-table td{padding:8px 0;border-bottom:1px solid var(--border,#e2e8f0)}
.info-table td:first-child{font-weight:600;color:var(--muted,#64748b);width:110px}
.info-table tr:last-child td{border-bottom:none}
.qr-wrap{display:flex;flex-direction:column;align-items:center;gap:8px;padding:12px 0}
.qr-wrap canvas{border-radius:12px;box-shadow:0 4px 16px rgba(0,0,0,.1)}
.perm-badge{display:inline-flex;align-items:center;gap:6px;background:var(--bg-muted,#f1f5f9);border:1px solid var(--border,#e2e8f0);border-radius:8px;padding:8px 14px;font-size:13px;font-weight:600}
.perm-badge i{color:var(--primary);font-size:1rem}
.install-steps{display:flex;gap:8px;margin-top:16px;flex-wrap:wrap}
.install-step{flex:1;min-width:100px;text-align:center;padding:12px 8px;border-radius:10px;background:var(--bg-muted,#f8fafc)}
.install-step .step-num{width:24px;height:24px;border-radius:50%;background:var(--primary,#0d6efd);color:#fff;font-size:11px;font-weight:700;display:inline-flex;align-items:center;justify-content:center;margin-bottom:6px}
.install-step .step-label{font-size:11px;font-weight:600;color:var(--muted,#64748b);display:block}
@keyframes fadeUp{from{opacity:0;transform:translateY(24px)}to{opacity:1;transform:translateY(0)}}
.fade-up{animation:fadeUp .45s ease forwards;opacity:0}
.fade-up:nth-child(1){animation-delay:.05s}
.fade-up:nth-child(2){animation-delay:.1s}
.fade-up:nth-child(3){animation-delay:.15s}
.fade-up:nth-child(4){animation-delay:.2s}
.fade-up:nth-child(5){animation-delay:.25s}
</style>

<div class="container py-4">
  <div class="app-hero">
    <img src="assets/img/pwa-icon-192.svg" alt="BD-Fashion" class="app-icon">
    <h1>BD-Fashion App</h1>
    <p><?php echo htmlspecialchars($appTagline); ?></p>
  </div>

  <div class="row g-4 mt-2">
    <div class="col-md-6 fade-up">
      <div class="platform-card d-flex flex-column">
        <div class="card-head">
          <div class="icon-box" style="background:#fce4ec;color:#c62828"><i class="bi bi-android2"></i></div>
          <div>
            <h5>Android APK</h5>
            <span class="badge bg-success" style="font-size:10px">Direct</span>
          </div>
        </div>
        <p class="sub">Version <?php echo $appVersion; ?> &bull; Android 8+</p>
        <?php if ($apkExists): ?>
          <div class="qr-wrap">
            <div id="apkQr"></div>
            <span class="small" style="color:var(--muted)">Scan with your phone</span>
          </div>
          <a href="mobile-app/bd-fashion.apk" class="dl-btn success w-100" download>
            <i class="bi bi-download"></i> Download (<?php echo $apkSize; ?> MB)
          </a>
          <div class="small text-center mt-2" style="color:var(--muted)">Updated <?php echo $apkModified; ?></div>
        <?php else: ?>
          <div class="text-center py-4" style="color:var(--muted)">
            <i class="bi bi-hourglass-split" style="font-size:2rem;display:block;margin-bottom:8px"></i>
            <span class="small">APK not yet available</span>
          </div>
        <?php endif; ?>
        <div class="install-steps mt-auto">
          <div class="install-step"><div class="step-num">1</div><span class="step-label">Download file</span></div>
          <div class="install-step"><div class="step-num">2</div><span class="step-label">Open &amp; Install</span></div>
          <div class="install-step"><div class="step-num">3</div><span class="step-label">Allow unknown apps</span></div>
        </div>
      </div>
    </div>

    <div class="col-md-6 fade-up">
      <div class="platform-card d-flex flex-column">
        <div class="card-head">
          <div class="icon-box" style="background:#e3f2fd;color:#1565c0"><i class="bi bi-apple"></i></div>
          <div>
            <h5>iPhone / iPad</h5>
            <span class="badge bg-warning text-dark" style="font-size:10px">Safari</span>
          </div>
        </div>
        <p class="sub">iOS 14+ &bull; Safari required</p>
        <div class="text-center py-3">
          <div style="font-size:2.5rem;color:var(--primary);margin-bottom:4px"><i class="bi bi-square-up-right"></i></div>
          <div style="font-weight:700;font-size:15px">Add to Home Screen</div>
          <div class="small" style="color:var(--muted)">Share sheet in Safari</div>
        </div>
        <div class="install-steps mt-auto">
          <div class="install-step"><div class="step-num">1</div><span class="step-label">Tap Share icon</span></div>
          <div class="install-step"><div class="step-num">2</div><span class="step-label">Add to Home Screen</span></div>
          <div class="install-step"><div class="step-num">3</div><span class="step-label">Tap Add</span></div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4 mt-4">
    <div class="col-lg-7 fade-up">
      <div class="platform-card">
        <div class="card-head" style="margin-bottom:20px">
          <div class="icon-box" style="background:#fef3c7;color:#d97706"><i class="bi bi-stars"></i></div>
          <h5 style="margin:0">App Features</h5>
        </div>
        <div class="feature-grid">
          <?php $featureIcons = ['rocket-takeoff','wifi-off','bell','fullscreen','arrow-repeat','battery-charging','shield-check','hand-index-thumb']; foreach ($features as $i => $f): ?>
          <div class="feature-item"><i class="bi bi-<?php echo $featureIcons[$i]; ?>"></i><span><?php echo htmlspecialchars($f); ?></span></div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div class="col-lg-5 fade-up">
      <div class="platform-card">
        <div class="card-head" style="margin-bottom:20px">
          <div class="icon-box" style="background:#e0e7ff;color:#4338ca"><i class="bi bi-info-circle"></i></div>
          <h5 style="margin:0">App Info</h5>
        </div>
        <table class="info-table">
          <tr><td>Version</td><td><?php echo htmlspecialchars($appVersion); ?></td></tr>
          <tr><td>Platform</td><td>Android 8+ (API 26)</td></tr>
          <tr><td>Arch</td><td>ARM64 / x86_64</td></tr>
          <?php if ($apkSize): ?><tr><td>Size</td><td><?php echo $apkSize; ?> MB</td></tr><?php endif; ?>
          <tr><td>Built</td><td><?php echo htmlspecialchars($appVersionDate); ?></td></tr>
          <tr><td>License</td><td>Free</td></tr>
          <tr><td>Type</td><td>Debug (self-signed)</td></tr>
        </table>
      </div>
    </div>
  </div>

  <div class="row mt-4 fade-up">
    <div class="col-12">
      <div class="platform-card">
        <div class="card-head" style="margin-bottom:16px">
          <div class="icon-box" style="background:#fce7f3;color:#be185d"><i class="bi bi-shield-lock"></i></div>
          <h5 style="margin:0">Permissions</h5>
        </div>
        <p class="small" style="color:var(--muted);margin-bottom:16px">The APK requests these permissions for full functionality:</p>
        <div class="d-flex gap-2 flex-wrap">
          <span class="perm-badge"><i class="bi bi-wifi"></i> INTERNET</span>
          <span class="perm-badge"><i class="bi bi-folder2"></i> STORAGE</span>
          <span class="perm-badge"><i class="bi bi-bell"></i> NOTIFICATIONS</span>
          <span class="perm-badge"><i class="bi bi-phone-vibrate"></i> VIBRATE</span>
        </div>
      </div>
    </div>
  </div>

  <?php if (!$apkExists): ?>
  <div class="row mt-4 fade-up">
    <div class="col-12">
      <div class="platform-card text-center py-4">
        <i class="bi bi-cloud-arrow-up" style="font-size:2rem;color:var(--primary);display:block;margin-bottom:8px"></i>
        <h5 class="mb-2">Build the APK</h5>
        <p class="small" style="color:var(--muted);margin-bottom:16px">Push to GitHub and run Actions, or use PWABuilder:</p>
        <div class="d-flex justify-content-center gap-3 flex-wrap">
          <a href="https://github.com" target="_blank" class="dl-btn outline" style="font-size:13px;padding:10px 24px"><i class="bi bi-github"></i> GitHub Actions</a>
          <a href="https://pwabuilder.com" target="_blank" class="dl-btn primary" style="font-size:13px;padding:10px 24px"><i class="bi bi-box-arrow-up-right"></i> PWABuilder.com</a>
        </div>
        <p class="small mt-2 mb-0" style="color:var(--muted)">Enter <code>https://bd-fashion.free.nf</code> &rarr; Android Package</p>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
var apkQr = document.getElementById('apkQr');
if (apkQr && typeof QRCode !== 'undefined') {
  var apkUrl = window.location.origin + window.location.pathname.replace(/\/[^/]*$/, '') + '/mobile-app/bd-fashion.apk';
  new QRCode(apkQr, { text: apkUrl, width: 140, height: 140, colorDark: '#0d6efd', colorLight: '#ffffff' });
}
</script>

<?php require_once __DIR__.'/templates/footer.php'; ?>
