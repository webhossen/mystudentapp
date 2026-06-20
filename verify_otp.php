<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/otp_handler.php'; // Required to bridge with your database engine
session_start();

header('Content-Type: application/json');

// 1. Enforce strict HTTP POST request method usage
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// 2. Normalize JSON content types alongside typical standard POST data payloads
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$otp = trim($input['otp'] ?? '');
// Automatically extract the active email from the session state assigned during step 1
$email = $_SESSION['otp_verify_email'] ?? ''; 

// 3. Early validation guard checks
if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'No active verification session found. Please request a new OTP.']);
    exit;
}

if (empty($otp)) {
    echo json_encode(['success' => false, 'message' => 'Please provide the verification code.']);
    exit;
}

// 4. Initialize Database Context
try {
    $pdo = getPDO();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal database connection error.']);
    exit;
}

// 5. Delegate verification logic processing execution to our secure handler
$result = verifyOTP($email, $otp, $pdo);

if ($result['success']) {
    // Elevate authentication access tokens on success
    $_SESSION['email_otp_verified'] = true;
    $_SESSION['email_otp_verified_address'] = $email;
    
    // Clean temporary session data
    unset($_SESSION['otp_verify_email']);

    echo json_encode([
        'success' => true, 
        'message' => 'OTP verified successfully.'
    ]);
} else {
    // If the attempt limit threshold fails inside verifyOTP(), it automatically burns the tokens.
    echo json_encode([
        'success' => false, 
        'message' => $result['message']
    ]);
}
exit;