<?php
$maintenanceHeading = getSetting('maintenance_heading', 'We\'ll be back soon!');
$maintenanceMessage = getSetting('maintenance_message', 'Our website is currently undergoing scheduled maintenance. We apologize for the inconvenience and will be back up shortly.');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Under Maintenance - <?php echo htmlspecialchars(getSetting('store_name', APP_NAME)); ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { font-family: 'Inter', sans-serif; background: #0b1120; color: #e2e8f0; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 2rem; text-align: center; }
    .maintenance-icon { font-size: 4rem; margin-bottom: 1.5rem; display: block; }
    h1 { font-weight: 800; font-size: 2rem; margin-bottom: 1rem; }
    p { color: #94a3b8; max-width: 460px; margin: 0 auto 2rem; line-height: 1.7; }
    .brand { font-weight: 700; color: #60a5fa; margin-bottom: 0.5rem; font-size: 1.1rem; }
  </style>
</head>
<body>
  <div>
    <div class="brand"><?php echo htmlspecialchars(getSetting('store_name', APP_NAME)); ?></div>
    <div class="maintenance-icon">🔧</div>
    <h1><?php echo htmlspecialchars($maintenanceHeading); ?></h1>
    <p><?php echo htmlspecialchars($maintenanceMessage); ?></p>
    <div style="font-size:0.8rem;color:#64748b">Please check back later.</div>
  </div>
</body>
</html>
