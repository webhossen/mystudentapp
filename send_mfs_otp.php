<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/otp_handler.php';
require_once __DIR__ . '/config/sms.php';
require_once __DIR__ . '/config/email.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$phone = preg_replace('/[^0-9]/', '', trim($_POST['phone'] ?? ''));
$provider = trim($_POST['provider'] ?? '');

if (!in_array($provider, ['bkash', 'nagad', 'rocket'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment provider.']);
    exit;
}

if (strlen($phone) < 10) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid 11-digit MFS account number.']);
    exit;
}

// Use the logged-in user's email as fallback for OTP delivery
$userEmail = '';
if (!empty($_SESSION['user_id'])) {
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare('SELECT email, name FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        $userEmail = $user['email'] ?? '';
        $customerName = $user['name'] ?? 'Valued Customer';
    } catch (Exception $e) {}
}

if (empty($userEmail)) {
    echo json_encode(['success' => false, 'message' => 'Session expired. Please login again.']);
    exit;
}

// Identifier for OTP storage
$mfsId = 'mfs_' . $provider . '_' . $phone;

try {
    $pdo = getPDO();

    // Rate limit check
    $stmt = $pdo->prepare(
        'SELECT TIMESTAMPDIFF(SECOND, created_at, NOW()) AS seconds_ago
           FROM email_verifications
          WHERE email = ? AND is_verified = 0
          ORDER BY created_at DESC LIMIT 1'
    );
    $stmt->execute([$mfsId . '@localhost']);
    $last = $stmt->fetch();

    if ($last && $last['seconds_ago'] < 60) {
        $wait = 60 - (int)$last['seconds_ago'];
        echo json_encode(['success' => false, 'message' => "Please wait {$wait}s before requesting a new code."]);
        exit;
    }

    // Generate OTP
    $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $otpTtlMin = 5;

    // Store in email_verifications
    $ins = $pdo->prepare(
        'INSERT INTO email_verifications (email, otp_code, expires_at)
         VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE))'
    );
    $ins->execute([$mfsId . '@localhost', $otp, $otpTtlMin]);

    // Send OTP via SMS
    $providerLabels = ['bkash' => 'bKash', 'nagad' => 'Nagad', 'rocket' => 'Rocket'];
    $providerLabel = $providerLabels[$provider] ?? ucfirst($provider);
    $smsResult = sendSMS($phone, "Your {$providerLabel} payment OTP: {$otp}. It expires in {$otpTtlMin} minutes. - " . getSetting('store_name', APP_NAME));

    // Also send via email as backup
    try {
        $mail = getMailer();
        $mail->addAddress($userEmail);
        $siteName = htmlspecialchars(getSetting('store_name', APP_NAME));
        $mail->Subject = "{$providerLabel} Payment OTP - {$siteName}";
        $mail->Body = "<h2>{$providerLabel} Payment Verification</h2>
<p>Hello {$customerName},</p>
<p>You are authorizing a payment via <strong>{$providerLabel}</strong> (Account: {$phone}).</p>
<div style='background:#f5f3ff;border-radius:8px;padding:20px;text-align:center;margin:20px 0'>
    <p style='font-size:28px;letter-spacing:6px;font-weight:700;color:#8b5cf6;font-family:monospace;margin:0'>{$otp}</p>
    <p style='color:#475569;font-size:14px;margin-top:12px'>This code expires in {$otpTtlMin} minutes.</p>
</div>
<p>If you did not make this request, please ignore this email.</p>
<p>Best regards,<br>The {$siteName} Team</p>";
        $mail->AltBody = "Your {$providerLabel} payment OTP is: {$otp}. It expires in {$otpTtlMin} minutes.";
        $mail->send();
    } catch (Exception $e) {
        error_log('MFS OTP email fallback failed: ' . $e->getMessage());
    }

    // Store session state
    $_SESSION['mfs_otp_phone'] = $phone;
    $_SESSION['mfs_otp_provider'] = $provider;
    $_SESSION['mfs_otp_id'] = $mfsId;

    $masked = substr($phone, 0, 3) . '****' . substr($phone, -2);
    echo json_encode(['success' => true, 'message' => "OTP sent to {$masked}"]);

} catch (Exception $e) {
    error_log('send_mfs_otp error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to send OTP. Please try again.']);
}
