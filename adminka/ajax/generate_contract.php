<?php
require '../functions.php';
require '../vendor/autoload.php';
require '../templates/contract_template.php';

$orderId = (int)$_GET['order_id'] ?? 0;
$userId = getUserId();

$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$orderId, $userId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    http_response_code(404);
    echo "Заказ не найден.";
    exit;
}

$items = json_decode($order['items'], true);
$partner = json_decode($order['partner_data'], true);
$total = $order['total'];

$html = renderContractHTML($partner, $items, $orderId, $total);

$pdf = new \TCPDF();
$pdf->AddPage();
$pdf->writeHTML($html, true, false, true, false, '');
$filePath = "../invoices/contract_$orderId.pdf";
$pdf->Output($filePath, 'F');

echo json_encode(['url' => $filePath]);
