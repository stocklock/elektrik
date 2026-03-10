<?php
require '../functions.php';

$userId = getUserId();
$productId = $_POST['product_id'];

$stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
$stmt->execute([$userId, $productId]);

echo "Removed";
