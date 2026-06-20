<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/otp_handler.php';
session_start();

$message = '';
$messageType = '';

$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';

    if (isset($_POST['send_otp'])) {
        $customerName = trim($_POST['customer_name'] ?? 'Valued Customer');
        $orderId = trim($_POST['order_id'] ?? '');
        $result = sendOTP($email, $pdo, $customerName, $orderId);
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
    } elseif (isset($_POST['verify_otp'])) {
        $otp = $_POST['otp'] ?? '';
        $result = verifyOTP($email, $otp, $pdo);
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';

        if ($result['success']) {
            $_SESSION['email_verified'] = $email;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f8fafc; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .card { background: #fff; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); padding: 40px; max-width: 440px; width: 100%; }
        h1 { font-size: 22px; color: #1e1b4b; margin-bottom: 8px; }
        p { color: #64748b; font-size: 14px; margin-bottom: 24px; line-height: 1.5; }
        .form-group { margin-bottom: 16px; }
        label { display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px; }
        input[type="email"], input[type="text"] { width: 100%; padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 15px; outline: none; transition: border-color 0.2s; }
        input:focus { border-color: #8b5cf6; }
        .btn { width: 100%; padding: 11px; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; transition: background 0.2s; }
        .btn-primary { background: #8b5cf6; color: #fff; }
        .btn-primary:hover { background: #7c3aed; }
        .btn-secondary { background: #f1f5f9; color: #334155; margin-top: 8px; }
        .btn-secondary:hover { background: #e2e8f0; }
        .message { padding: 12px 16px; border-radius: 8px; font-size: 14px; margin-bottom: 20px; }
        .message.success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .message.error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        hr { border: none; border-top: 1px solid #e2e8f0; margin: 24px 0; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Email Verification</h1>
        <p>Enter your email to receive a verification code.</p>

        <?php if ($message): ?>
            <div class="message <?= htmlspecialchars($messageType) ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="post">
            <?php echo csrfFieldFront(); ?>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" name="email" id="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
            <button type="submit" name="send_otp" class="btn btn-primary">Send OTP</button>

            <hr>

            <div class="form-group">
                <label for="otp">Verification Code</label>
                <input type="text" name="otp" id="otp" maxlength="6" pattern="\d{6}" inputmode="numeric" placeholder="000000">
            </div>
            <button type="submit" name="verify_otp" class="btn btn-primary">Verify Code</button>
        </form>
    </div>
</body>
</html>
