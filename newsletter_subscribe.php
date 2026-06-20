<?php
require_once __DIR__.'/config/config.php';
session_start();

if($_SERVER['REQUEST_METHOD'] === 'POST'){
  verifyCsrfFront();
  $email = trim($_POST['email'] ?? '');
  if(filter_var($email, FILTER_VALIDATE_EMAIL)){
    try{
      $pdo = getPDO();
      $stmt = $pdo->prepare('INSERT IGNORE INTO newsletters (email) VALUES (?)');
      $stmt->execute([$email]);
      setFlash('Subscribed successfully!');
    } catch(Exception $e){
      setFlash('Something went wrong.', 'danger');
    }
  } else {
    setFlash('Invalid email address.', 'danger');
  }
}

header('Location: index.php#newsletter');
exit;
