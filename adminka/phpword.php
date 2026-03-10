<?php
// Конфигурация базы данных
$host = 'localhost';
$db   = 'db';
$user = 'user';
$pass = 'pass';
$charset = 'utf8mb4';
require_once '../PHPWord/src/PhpWord/Autoloader.php';
\PhpOffice\PhpWord\Autoloader::register();

use PhpOffice\PhpWord\TemplateProcessor;

// DSN и подключение через PDO
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Ошибка подключения к базе: " . $e->getMessage());
}

// Путь к шаблону
$templatePath = 'template.docx';

// Создаём объект шаблона
$templateProcessor = new TemplateProcessor($templatePath);

// Заменяем переменные
$templateProcessor->setValue('company_name' , htmlspecialchars($client['company_name']));
$templateProcessor->setValue('inn' , htmlspecialchars($client['inn']));
$templateProcessor->setValue('ogrnip' , htmlspecialchars($client['ogrnip']));
$templateProcessor->setValue('legal_address' , htmlspecialchars($client['legal_address']));
$templateProcessor->setValue('actual_address' , htmlspecialchars($client['actual_address']));
$templateProcessor->setValue('phone' , htmlspecialchars($client['phone']));
$templateProcessor->setValue('checking_account' , htmlspecialchars($client['checking_account']));
$templateProcessor->setValue('correspondent_account' , htmlspecialchars($client['correspondent_account']));
$templateProcessor->setValue('bik' , htmlspecialchars($client['bik']));
$templateProcessor->setValue('bank_name' , htmlspecialchars($client['bank_name']));
$templateProcessor->setValue('estimate_sum' , htmlspecialchars($client['estimate_sum']));
$templateProcessor->setValue('contract_date' , date('d.m.Y'));

// Сохраняем во временный файл
$tmpFile = tempnam(sys_get_temp_dir(), 'docx_') . '.docx';
$templateProcessor->saveAs($tmpFile);

// Читаем содержимое файла
$docContent = file_get_contents($tmpFile);

// Имя файла для сохранения в базе
$filename = 'generated_' . date('Ymd_His') . '.docx';

// Подготовка и выполнение запроса на вставку
$sql = "INSERT INTO documents (name, content, created_at) VALUES (:filename, :doc_content, NOW()))";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':filename', $filename);
$stmt->bindParam(':doc_content', $docContent, PDO::PARAM_LOB);
$stmt->execute();

// Удаляем временный файл
unlink($tmpFile);

echo "Документ успешно сохранён в базе данных с именем: $filename\n";
