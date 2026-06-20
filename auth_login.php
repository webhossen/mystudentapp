<?php
require_once __DIR__.'/config/config.php';
session_start();

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    verifyCsrfFront();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if(!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === ''){
        setFlash('Invalid email or password.', 'danger');
        header('Location: login.php'); exit;
    }

    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT id,name,password_hash,status,avatar FROM users WHERE email=?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if(!$user || !password_verify($password, $user['password_hash'])){
        setFlash('Invalid credentials.', 'danger');
        header('Location: login.php'); exit;
    }
    if(isset($user['status']) && $user['status'] == 0){
        setFlash('Your account is blocked. Please contact support.', 'danger');
        header('Location: login.php'); exit;
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_avatar'] = $user['avatar'] ?? null;
    header('Location: index.php'); exit;
}

header('Location: login.php');
exit;
