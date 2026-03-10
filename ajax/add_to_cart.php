<?php
require '../functions.php';

$userId = getUserId();
$productId = $_POST['product_id'];

$stmt = $pdo->prepare("SELECT id FROM cart WHERE user_id = ? AND product_id = ?");
$stmt->execute([$userId, $productId]);

if ($row = $stmt->fetch()) {
    $stmt = $pdo->prepare("UPDATE cart SET quantity = quantity + 1 WHERE id = ?");
    $stmt->execute([$row['id']]);
} else {
    $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1)");
    $stmt->execute([$userId, $productId]);
}

echo "Added to cart";
