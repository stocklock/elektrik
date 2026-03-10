<?php
header('Content-Type: application/json');
require '../db.php';
require '../vendor/autoload.php';
require '../functions.php';

use TCPDF;

// API URL
define('LKNPD_API', 'https://lknpd.nalog.ru');

function apiRequest($method, $url, $data = null, $token = null) {
    $headers = ['Content-Type: application/json'];
    if ($token) $headers[] = "Authorization: Bearer $token";

    $opts = [
        "http" => [
            "method" => $method,
            "header" => implode("\r\n", $headers),
            "content" => $data ? json_encode($data) : ''
        ]
    ];
    return file_get_contents(LKNPD_API . $url, false, stream_context_create($opts));
}

// Данные
$inn = preg_replace('/\D/', '', $_POST['inn'] ?? '');
$phone = preg_replace('/[^\d\+]/', '', $_POST['phone'] ?? '');
$serviceName = sanitize($_POST['service_name'] ?? '');
$price = floatval($_POST['price'] ?? 0);

// Валидация
if (strlen($inn) !== 12 || !$phone || !$serviceName || $price <= 0) {
    echo json_encode(['error' => 'Неверные данные']);
    exit;
}

// Авторизация
$auth = apiRequest('POST', '/api/v1/auth', [
    'inn' => $inn,
    'client_secret' => md5($inn)
]);
$authData = json_decode($auth, true);
if (empty($authData['token'])) {
    echo json_encode(['error' => 'Ошибка авторизации']);
    exit;
}
$token = $authData['token'];

// Отправка чека
$receipt = apiRequest('POST', '/api/v1/receipt', [
    'phone' => $phone,
    'services' => [[
        'name' => $serviceName,
        'price' => $price,
        'quantity' => 1
    ]],
    'amount' => $price,
], $token);
$receiptData = json_decode($receipt, true);

if (!empty($receiptData['status']) && $receiptData['status'] === 'OK') {
    $checkId = $receiptData['id'];

    // Сохраняем в БД
    $stmt = $pdo->prepare("INSERT INTO invoices (inn, phone, service, amount, check_id)
                           VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$inn, $phone, $serviceName, $price, $checkId]);
    $invoiceId = $pdo->lastInsertId();

    // PDF
    $pdf = new TCPDF();
    $pdf->AddPage();
    $html = "<h2>Счёт №$checkId</h2>
             <p>ИНН: $inn<br>Телефон: $phone<br>Услуга: $serviceName<br>Сумма: $price ₽</p>
             <p>Подпись:<br><img src=\"../signature.png\" width=\"100\"></p>";
    $pdf->writeHTML($html);
    $pdfPath = "../invoices/invoice_$invoiceId.pdf";
    $pdf->Output($pdfPath, 'F');

    // Email
    $to = "client@example.com"; // Заменить на email клиента
    $subject = "Ваш счёт №$checkId";
    $message = "Во вложении — PDF копия счёта.";
    $content = chunk_split(base64_encode(file_get_contents($pdfPath)));
    $uid = md5(uniqid(time()));
    $header = "From: shop@example.com\r\n";
    $header .= "MIME-Version: 1.0\r\n";
    $header .= "Content-Type: multipart/mixed; boundary=\"".$uid."\"\r\n\r\n";
    $body = "--".$uid."\r\n";
    $body .= "Content-type:text/plain; charset=utf-8\r\n\r\n";
    $body .= $message."\r\n\r\n";
    $body .= "--".$uid."\r\n";
    $body .= "Content-Type: application/pdf; name=\"invoice.pdf\"\r\n";
    $body .= "Content-Transfer-Encoding: base64\r\n";
    $body .= "Content-Disposition: attachment; filename=\"invoice.pdf\"\r\n\r\n";
    $body .= $content."\r\n\r\n";
    $body .= "--".$uid."--";

    mail($to, $subject, $body, $header);

    echo json_encode([
        'success' => true,
        'check_id' => $checkId,
        'pdf' => $pdfPath
    ]);
} else {
    echo json_encode(['error' => 'Ошибка отправки чека', 'details' => $receiptData]);
}
?>