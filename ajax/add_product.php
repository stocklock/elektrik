<?php
require '../functions.php';

$userId = getUserId();
$name = $_POST['name'];
$price = $_POST['price'];
$desc = $_POST['description'];

$stmt = $pdo->prepare("INSERT INTO products (user_id, name, price, description) VALUES (?, ?, ?, ?)");
$stmt->execute([$userId, $name, $price, $desc]);

echo "OK";
