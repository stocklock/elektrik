<?php
require '../functions.php';

$userId = getUserId();

if (isset($_GET['products'])) {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    if ($search !== '') {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE user_id = ? AND name LIKE ?");
        $stmt->execute([$userId, "%$search%"]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE user_id = ?");
        $stmt->execute([$userId]);
    }
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}


$stmt = $pdo->prepare("
    SELECT p.id AS product_id, p.name, p.price, c.quantity, (p.price * c.quantity) AS total
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
");
$stmt->execute([$userId]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
