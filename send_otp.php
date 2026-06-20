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

$email = trim($_POST['email'] ?? '');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}

try {
    $pdo = getPDO();

    $stmt = $pdo->prepare('SELECT name FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $customerName = $stmt->fetchColumn();
    if (!$customerName) {
        $customerName = 'Valued Customer';
    }

    $result = sendOTP($email, $pdo, $customerName);

    if ($result['success']) {
        $_SESSION['otp_verify_email'] = $email;
        $parts = explode('@', $email);
        $masked = substr($parts[0], 0, 2) . '***@' . $parts[1];
        echo json_encode(['success' => true, 'message' => "OTP sent to $masked"]);
    } else {
        echo json_encode($result);
    }
} catch (Exception $e) {
    error_log('send_otp error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to send OTP. Please try again.']);
}
