<?php
require_once __DIR__.'/config/config.php';
require_once __DIR__.'/templates/header.php';
echo '<div class="alert alert-danger">Payment was canceled or failed. Your cart is preserved.</div>';
echo '<a href="cart.php" class="btn btn-secondary">Back to Cart</a>';
require_once __DIR__.'/templates/footer.php';
