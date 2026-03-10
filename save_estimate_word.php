<?php
// Конфигурация базы данных
$host = 'localhost';
$db   = 'u1851662_adminka';
$user = 'u1851662_default';
$pass = '5OBw9nvRSA3Al7V0';  // здесь вставь свой пароль
$charset = 'utf8mb4';

require_once '../PHPWord/src/PhpWord/Autoloader.php';
\PhpOffice\PhpWord\Autoloader::register();

use PhpOffice\PhpWord\TemplateProcessor;

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
    // DSN и подключение через PDO
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

    // Путь к шаблону
    $templatePath = 'template_akt.docx';

    // Создаём объект шаблона
    $templateProcessor = new TemplateProcessor($templatePath);

    // Заменяем переменные в шаблоне
    $templateProcessor->setValue('company_name', htmlspecialchars($client['company_name']));
    $templateProcessor->setValue('inn', htmlspecialchars($client['inn']));
    $templateProcessor->setValue('ogrnip', htmlspecialchars($client['ogrnip']));
    $templateProcessor->setValue('legal_address', htmlspecialchars($client['legal_address']));
    $templateProcessor->setValue('actual_address', htmlspecialchars($client['actual_address']));
    $templateProcessor->setValue('phone', htmlspecialchars($client['phone']));
    $templateProcessor->setValue('checking_account', htmlspecialchars($client['checking_account']));
    $templateProcessor->setValue('correspondent_account', htmlspecialchars($client['correspondent_account']));
    $templateProcessor->setValue('bik', htmlspecialchars($client['bik']));
    $templateProcessor->setValue('bank_name', htmlspecialchars($client['bank_name']));
    $templateProcessor->setValue('estimate_sum', htmlspecialchars($client['estimate_sum']));
    $templateProcessor->setValue('contract_date', date('d.m.Y'));
// Получаем все записи из estimate_items
$sqlItemssmeta = "SELECT description, quantity, ed, price, sum_price FROM estimate_items WHERE estimate_id=$estimateId";
$stmtItemssmeta = $pdo->query($sqlItemssmeta);
$itemssmeta = $stmtItemssmeta->fetchAll(PDO::FETCH_ASSOC);

// Клонируем строки таблицы под количество элементов
$templateProcessor->cloneRow('it', count($itemssmeta));  // 'itemsmeta' — это имя плейсхолдера в шаблоне

foreach ($itemssmeta as $index => $it) {
    // Индекс для cloneRow начинается с 1
    $i = $index + 1;
	$templateProcessor->setValue("n#$i", $i);
    $templateProcessor->setValue("description#$i", htmlspecialchars($it['description']));
	$templateProcessor->setValue("ed#$i", htmlspecialchars($it['ed']));
    $templateProcessor->setValue("q#$i", htmlspecialchars($it['quantity']));
    $templateProcessor->setValue("price#$i", htmlspecialchars(number_format($it['price'], 2, ',', ' ')));
    $templateProcessor->setValue("sum_price#$i", htmlspecialchars(number_format($it['sum_price'], 2, ',', ' ')));
}
    // Сохраняем во временный файл
    $tmpFile = tempnam(sys_get_temp_dir(), 'docx_') . '.docx';
    $templateProcessor->saveAs($tmpFile);

    // Читаем содержимое файла
    $docContent = file_get_contents($tmpFile);

    // Имя файла для сохранения в базе
    $filename = 'generated_' . date('Ymd_His') . '.docx';

    // Подготовка и выполнение запроса на вставку документа
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
