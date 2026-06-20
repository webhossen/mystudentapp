<?php
require_once __DIR__.'/config/config.php';
session_start();
if($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: contact.php'); exit; }

verifyCsrfFront();

$email = trim($_POST['email'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

if(!$email || !filter_var($email, FILTER_VALIDATE_EMAIL) || !$subject || !$message){ $_SESSION['support_flash']='All fields are required.'; header('Location: contact.php'); exit; }

$pdo = getPDO();
$user_id = $_SESSION['user_id'] ?? null;
$ins = $pdo->prepare('INSERT INTO tickets (user_id,email,subject,message) VALUES (?,?,?,?)');
$ins->execute([$user_id,$email,$subject,$message]);
$ticket_id = $pdo->lastInsertId();

// initial reply stored as ticket message; admin replies go to ticket_replies
$rep = $pdo->prepare('INSERT INTO ticket_replies (ticket_id,sender,message) VALUES (?,?,?)');
$rep->execute([$ticket_id, 'user', $message]);

$_SESSION['support_flash']='Your message has been received. Ticket ID: '.$ticket_id;
header('Location: contact.php'); exit;
