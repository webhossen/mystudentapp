<?php
require_once __DIR__.'/config/config.php';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    verifyCsrfFront();
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if(!$name || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8){
        setFlash('Please provide valid name, email and password (min 8 chars).', 'danger');
        header('Location: signup.php'); exit;
    }

    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email=?');
    $stmt->execute([$email]);
    $existingUser = $stmt->fetch();
    if($existingUser){
        setFlash('Registration failed. Please try again.', 'danger');
        header('Location: signup.php'); exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (name,email,`password_hash`) VALUES (?,?,?)');
    $stmt->execute([$name,$email,$hash]);

    session_regenerate_id(true);
    $_SESSION['user_id'] = $pdo->lastInsertId();
    $_SESSION['user_name'] = $name;
    header('Location: index.php'); exit;
}

header('Location: signup.php');
exit;
