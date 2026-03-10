<?php
$host = 'localhost';
$db   = 'db';
$user = 'user';
$pass = 'pass';
$charset = 'utf8mb4';

// Подключение к базе
$mysqli = new mysqli($host, $user, $pass, $db);
if ($mysqli->connect_error) {
    die("Ошибка подключения: " . $mysqli->connect_error);
}

// Получение клиента
$clientId = $_POST['partner_card_id'];
$stmt = $mysqli->prepare("SELECT * FROM partner_card WHERE id = ?");
$stmt->bind_param("i", $clientId);
$stmt->execute();
$result = $stmt->get_result();
$client = $result->fetch_assoc();

if (!$client) {
    die("Клиент не найден.");
}


// Пример данных из базы:
$replacements = [
    'company_name' => htmlspecialchars($client['company_name']),
    'inn' => htmlspecialchars($client['inn']),
	'ogrnip' => htmlspecialchars($client['ogrnip']),
	'legal_address' => htmlspecialchars($client['legal_address']),
	'actual_address' => htmlspecialchars($client['actual_address']),
	'phone' => htmlspecialchars($client['phone']),
	'checking_account' => htmlspecialchars($client['checking_account']),
	'correspondent_account' => htmlspecialchars($client['correspondent_account']),
	'bik' => htmlspecialchars($client['bik']),
	'bank_name' => htmlspecialchars($client['bank_name']),
    'estimate_sum' => htmlspecialchars($client['estimate_sum']),
    'contract_date' => date('d.m.Y'),
];
function generateDocxFromTemplate($templatePath, $outputPath, $replacements) {
    $zip = new ZipArchive();
    if ($zip->open($templatePath) === TRUE) {
        // Читаем XML основного документа
        $xml = $zip->getFromName('word/document.xml');

        // Заменяем плейсхолдеры
        foreach ($replacements as $key => $value) {
            // Word может разбить {{place}} на {{pl}}...{{ace}}, поэтому удалим теги внутри
            $pattern = '/\{\{' . preg_quote($key, '/') . '\}\}/';
            $xml = preg_replace($pattern, $value, $xml);
            $xml = str_replace('{{' . $key . '}}', htmlspecialchars($value), $xml);
        }

        // Перезаписываем document.xml
        $zip->deleteName('word/document.xml');
        $zip->addFromString('word/document.xml', $xml);

        // Сохраняем новый docx
        $zip->close();
        copy($templatePath, $outputPath); // копируем шаблон
        $zip->open($outputPath);
        $zip->addFromString('word/document.xml', $xml);
        $zip->close();

        return true;
    } else {
        return false;
    }
}



// Путь к шаблону и финальному файлу
$templatePath = __DIR__ . '/template.docx';
$outputPath = __DIR__ . '/contract_final.docx';

if (generateDocxFromTemplate($templatePath, $outputPath, $replacements)) {
    echo "Готово: <a href='contract_final.docx' download>Скачать договор</a>";
} else {
    echo "Ошибка генерации договора.";
}
?>
