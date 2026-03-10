<?php
require '../functions.php';

$userId = getUserId();

$stmt = $pdo->prepare("
    SELECT p.id, p.name, p.price, c.quantity
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
");
$stmt->execute([$userId]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$items) {
    echo "Корзина пуста";
    exit;
}

$total = 0;
foreach ($items as $item) {
    $total += $item['price'] * $item['quantity'];
}

$stmt = $pdo->prepare("INSERT INTO orders (user_id, items, total) VALUES (?, ?, ?)");
$stmt->execute([$userId, json_encode($items, JSON_UNESCAPED_UNICODE), $total]);

$pdo->prepare("DELETE FROM cart WHERE user_id = ?")->execute([$userId]);

echo "Заказ оформлен";
