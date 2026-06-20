<?php
require_once __DIR__.'/config/config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
  header('Location: login.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: index.php'); exit;
}

verifyCsrfFront();

$product_id = intval($_POST['product_id'] ?? 0);
$rating = intval($_POST['rating'] ?? 0);
$review = trim($_POST['review'] ?? '');

if (!$product_id || $rating < 1 || $rating > 5 || !$review) {
  setFlash('Please provide a rating and review text.', 'danger');
  header("Location: product_detail.php?id=$product_id"); exit;
}

$pdo = getPDO();

$stmt = $pdo->prepare('SELECT id FROM products WHERE id=? AND status=1');
$stmt->execute([$product_id]);
if (!$stmt->fetch()) {
  setFlash('Product not found.', 'danger');
  header("Location: product_detail.php?id=$product_id"); exit;
}

try {
  $stmt = $pdo->prepare('INSERT INTO product_reviews (product_id, user_id, rating, review) VALUES (?,?,?,?)');
  $stmt->execute([$product_id, $_SESSION['user_id'], $rating, $review]);
  setFlash('Review submitted successfully!');
} catch(Exception $e) {
  setFlash('Could not submit review. Please try again.', 'danger');
}
header("Location: product_detail.php?id=$product_id"); exit;
