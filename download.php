<?php
require_once __DIR__.'/config/config.php';
session_start();
if(empty($_SESSION['user_id'])){ header('Location: login.php'); exit; }

$item_id = isset($_GET['item'])?intval($_GET['item']):0;
if(!$item_id){ die('Invalid request'); }

$pdo = getPDO();
$stmt = $pdo->prepare('SELECT oi.*, o.user_id, o.status, p.file_path, p.title, o.created_at as order_date FROM order_items oi JOIN orders o ON oi.order_id=o.id JOIN products p ON oi.product_id=p.id WHERE oi.id=?');
$stmt->execute([$item_id]);
$row = $stmt->fetch();
if(!$row){ die('Item not found'); }
if($row['user_id'] != $_SESSION['user_id']){ http_response_code(403); die('Access denied'); }
if($row['status'] !== 'paid'){ die('Order not yet paid. Please wait for payment verification.'); }

// Optional expiry (days)
$expiry_days = 365; // allow downloads for 1 year
if(strtotime($row['order_date']) < strtotime("-{$expiry_days} days")){
    die('Download link expired');
}

$file = __DIR__ . '/' . $row['file_path'];
if(!file_exists($file) || strpos(realpath($file), realpath(__DIR__.'/uploads')) !== 0){
    die('File not available');
}

// Serve file
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="'.basename($file).'"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($file));
readfile($file);
exit;
