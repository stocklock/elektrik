<?php
require '../functions.php';
$userId = getUserId();

$stmt = $pdo->prepare("SELECT * FROM partner_card WHERE user_id = ?");
$stmt->execute([$userId]);
$partner = $stmt->fetch(PDO::FETCH_ASSOC);
echo json_encode($partner ?? []);
