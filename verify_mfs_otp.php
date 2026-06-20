<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/otp_handler.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$otp = trim($_POST['otp'] ?? '');
$mfsId = $_SESSION['mfs_otp_id'] ?? '';
$phone = $_SESSION['mfs_otp_phone'] ?? '';
$provider = $_SESSION['mfs_otp_provider'] ?? '';

if (empty($mfsId) || empty($phone) || empty($provider)) {
    echo json_encode(['success' => false, 'message' => 'No active verification session. Please request a new OTP.']);
    exit;
}

if (empty($otp)) {
    echo json_encode(['success' => false, 'message' => 'Please provide the verification code.']);
    exit;
}

try {
    $pdo = getPDO();
    $email = $mfsId . '@localhost';

    // Verify OTP using the handler
    $result = verifyOTP($email, $otp, $pdo);

    if ($result['success']) {
        $_SESSION['mfs_otp_verified'] = true;

        // Clear session data (keep minimal)
        unset($_SESSION['mfs_otp_id']);

        // Send confirmation SMS
        require_once __DIR__ . '/config/sms.php';
        require_once __DIR__ . '/config/email.php';
        $providerLabels = ['bkash' => 'bKash', 'nagad' => 'Nagad', 'rocket' => 'Rocket'];
        $providerLabel = $providerLabels[$provider] ?? ucfirst($provider);
        $siteName = getSetting('store_name', APP_NAME);

        sendSMS($phone, "Payment confirmed via {$providerLabel}. Thank you for your order! - {$siteName}");

        // Also email confirmation
        if (!empty($_SESSION['user_id'])) {
            try {
                $stmt = $pdo->prepare('SELECT email FROM users WHERE id = ?');
                $stmt->execute([$_SESSION['user_id']]);
                $userEmail = $stmt->fetchColumn();
                if ($userEmail) {
                    $mail = getMailer();
                    $mail->addAddress($userEmail);
                    $mail->Subject = "{$providerLabel} Payment Confirmed - {$siteName}";
                    $mail->Body = "<h2>Payment Successful via {$providerLabel}</h2>
<p>Your payment has been confirmed.</p>
<p>MFS Account: {$phone}<br>
Provider: {$providerLabel}</p>
<p>Thank you for your order!</p>";
                    $mail->send();
                }
            } catch (Exception $e) {}
        }

        echo json_encode([
            'success' => true,
            'message' => 'Payment verified successfully!',
            'provider' => $provider,
            'phone' => $phone
        ]);
    } else {
        echo json_encode($result);
    }
} catch (Exception $e) {
    error_log('verify_mfs_otp error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Verification failed. Please try again.']);
}
