<?php
// Конфигурация базы данных
$host = 'localhost';
$db   = 'u1851662_adminka';
$user = 'u1851662_default';
$pass = '5OBw9nvRSA3Al7V0'; // здесь вставь свой пароль
$charset = 'utf8mb4';
require '/var/www/u1851662/data/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = IOFactory::load('template_smeta.xlsx');
$sheet = $spreadsheet->getActiveSheet();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || empty($data['partner_card_id']) || empty($data['estimateItems'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Недостаточно данных']);
    exit;
}

$partnerCardId = intval($data['partner_card_id']);
$items = $data['estimateItems'];

try {
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    $pdo = new PDO($dsn, $user, $pass, $options);

    // Получаем данные клиента
    $stmt = $pdo->prepare("SELECT * FROM partner_card WHERE id = ?");
    $stmt->execute([$partnerCardId]);
    $client = $stmt->fetch();

    if (!$client) {
        http_response_code(404);
        echo json_encode(['error' => 'Клиент не найден']);
        exit;
    }

    // Сохраняем смету
    $stmt = $pdo->prepare("INSERT INTO estimates (partner_id) VALUES (?)");
    $stmt->execute([$partnerCardId]);
    $estimateId = $pdo->lastInsertId();

    // Сохраняем строки сметы с подсчетом суммы позиции
    $stmtItem = $pdo->prepare("INSERT INTO estimate_items (price_id, estimate_id, description, quantity, ed, price, sum_price) VALUES (?, ?, ?, ?, ?, ?, ?)");

    $totalSum = 0; // для подсчёта общей суммы по смете
    foreach ($items as $item) {
        $quantity = (int)$item['quantity'];
        $priceid = (int)$item['id'];
        $price = (float)$item['price'];
        $sum_price = $price * $quantity;
        $ed = $item['ed'];
        $totalSum += $sum_price;

        $description = $item['title'];

        $stmtItem->execute([$priceid, $estimateId, $description, $quantity, $ed, $price, $sum_price]);
    }

    // Обновляем поле total_amount у сохранённой сметы
    $updateStmt = $pdo->prepare("UPDATE estimates SET total_amount = ? WHERE id = ?");
    $updateStmt->execute([$totalSum, $estimateId]);

    // Записываем общую сумму в $client, чтобы использовать в шаблоне
    $client['estimate_sum'] = number_format($totalSum, 2, '.', '');

    // Получаем все записи из estimate_items
    $sqlItemssmeta = "SELECT description, quantity, ed, price, sum_price FROM estimate_items WHERE estimate_id = ?";
    $stmtItemssmeta = $pdo->prepare($sqlItemssmeta);
    $stmtItemssmeta->execute([$estimateId]);
    $itemssmeta = $stmtItemssmeta->fetchAll();
/*
    // Вписываем данные клиента в Excel (привязывай по своим координатам ячеек)
    $sheet->setCellValue('A1', 'Компания:');
    $sheet->setCellValue('B1', $client['company_name']);
    $sheet->setCellValue('A2', 'ИНН:');
    $sheet->setCellValue('B2', $client['inn']);
    $sheet->setCellValue('A3', 'ОГРНИП:');
    $sheet->setCellValue('B3', $client['ogrnip']);
    $sheet->setCellValue('A4', 'Юридический адрес:');
    $sheet->setCellValue('B4', $client['legal_address']);
    $sheet->setCellValue('A5', 'Фактический адрес:');
    $sheet->setCellValue('B5', $client['actual_address']);
    $sheet->setCellValue('A6', 'Телефон:');
    $sheet->setCellValue('B6', $client['phone']);
    $sheet->setCellValue('A7', 'Расчетный счет:');
    $sheet->setCellValue('B7', $client['checking_account']);
    $sheet->setCellValue('A8', 'Корр. счет:');
    $sheet->setCellValue('B8', $client['correspondent_account']);
    $sheet->setCellValue('A9', 'БИК:');
    $sheet->setCellValue('B9', $client['bik']);
    $sheet->setCellValue('A10', 'Банк:');
    $sheet->setCellValue('B10', $client['bank_name']);
    $sheet->setCellValue('A11', 'Сумма сметы:');
    $sheet->setCellValue('B11', $client['estimate_sum']);
    $sheet->setCellValue('A12', 'Дата:');
    
*/
	$sheet->setCellValue('B12', date('d.m.Y'));
    // Заполняем таблицу работ начиная с 15-й строки (например)
    $startRow = 15;

    foreach ($itemssmeta as $index => $item) {
        $row = $startRow + $index;
        $sheet->setCellValue("A$row", $index + 1); // Нумерация
        $sheet->setCellValue("B$row", $item['description']);
        $sheet->setCellValue("C$row", $item['quantity']);
        $sheet->setCellValue("D$row", $item['ed']);
        $sheet->setCellValue("E$row", number_format($item['price'], 2, ',', ' '));
        $sheet->setCellValue("F$row", number_format($item['sum_price'], 2, ',', ' '));
    }

    // Сохраняем Excel во временный файл
    $tmpFile = tempnam(sys_get_temp_dir(), 'xlsx_') . '.xlsx';
    $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save($tmpFile);

    // Читаем содержимое файла
    $docContent = file_get_contents($tmpFile);

    // Имя файла для сохранения в базе
    $filename = 'generated_' . date('Ymd_His') . '.xlsx';

    // Вставляем файл в базу
    $sql = "INSERT INTO documents (name, content, created_at) VALUES (:filename, :doc_content, NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':filename', $filename);
    $stmt->bindParam(':doc_content', $docContent, PDO::PARAM_LOB);
    $stmt->execute();

    // Формируем договор (можно дополнить реальным текстом)
    $contract = "Договор успешно создан.";

    // Сохраняем договор
    $stmt = $pdo->prepare("INSERT INTO contracts (estimate_id, partner_id, text) VALUES (?, ?, ?)");
    $stmt->execute([$estimateId, $partnerCardId, $contract]);

    // Удаляем временный файл
    unlink($tmpFile);

    echo json_encode(['success' => true, 'contract' => $contract, 'clientid' => $client['id']]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка БД: ' . $e->getMessage()]);
}
