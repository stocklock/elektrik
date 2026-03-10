<?php
require '../functions.php';

$productId = $_POST['product_id'];
$userId = getUserId();
$name = $_POST['name'];
$price = $_POST['price'];
$desc = $_POST['description'];

$stmt = $pdo->prepare("UPDATE products SET name = ?, price = ?, description = ? WHERE id = ? AND user_id = ?");
$stmt->execute([$name, $price, $desc, $productId, $userId]);

echo "Updated";
