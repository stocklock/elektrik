<?php
require '../auth.php';
require '../functions.php';
//requireAdmin();

$action = $_POST['action'] ?? '';

if ($action === 'list_users') {
    $res = $pdo->query("SELECT id, username FROM users")->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($res);
}
elseif ($action === 'get_user_orders') {
    $userId = (int)$_POST['user_id'];
    $stmt = $pdo->prepare("SELECT id, items, total, created_at, partner_data FROM orders WHERE user_id = ?");
    $stmt->execute([$userId]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
}
elseif ($action === 'delete_order') {
    $orderId = (int)$_POST['order_id'];
    $pdo->prepare("DELETE FROM orders WHERE id = ?")->execute([$orderId]);
    echo "OK";
}
// Добавляйте аналогично edit/delete для продуктов, partner_card, cart и т.д.
?>
