<?php
require_once '../PHPWord/src/PhpWord/Autoloader.php';
\PhpOffice\PhpWord\Autoloader::register();

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

// Параметры подключения к базе данных
$host = 'localhost';
$db   = 'u1851662_adminka';
$user = 'u1851662_default';
$pass = '5OBw9nvRSA3Al7V0';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

// Получаем данные из таблицы prices
$stmt = $pdo->query('SELECT title, ed, price FROM prices ORDER BY id');
$rows = $stmt->fetchAll();

if (!$rows) {
    die("Данные не найдены");
}

// Создаем новый документ Word
$phpWord = new PhpWord();

// Добавляем секцию
$section = $phpWord->addSection();

// Добавляем заголовок
$section->addText('Прайс-лист на электромонтажные услуги', ['bold' => true, 'size' => 16], ['alignment' => 'center']);
$section->addTextBreak(1);

// Создаем таблицу
$tableStyleName = 'PriceTable';
$tableStyle = [
    'borderSize' => 6,
    'borderColor' => '999999',
    'cellMargin' => 80,
];
$firstRowStyle = ['bgColor' => 'CCCCCC'];
$phpWord->addTableStyle($tableStyleName, $tableStyle, $firstRowStyle);

$table = $section->addTable($tableStyleName);
$i=0;
// Заголовки таблицы
$table->addRow();
$table->addCell(6000)->addText('№', ['bold' => true]);
$table->addCell(6000)->addText('Услуга', ['bold' => true]);
$table->addCell(1500)->addText('Количество', ['bold' => true]);
$table->addCell(1500)->addText('Ед.изм.', ['bold' => true]);
$table->addCell(2000)->addText('Цена (руб.)', ['bold' => true]);

// Заполняем таблицу данными
foreach ($rows as $row) {
    $table->addRow();
	$table->addCell(6000)->addText($i++);
    $table->addCell(6000)->addText($row['title']);
    $table->addCell(1500)->addText('1'); // Количество пустое (как указано)
    $table->addCell(1500)->addText($row['ed']);
    $table->addCell(2000)->addText($row['price']);
}

// Отдаем файл на скачивание
$fileName = "price_list_" . date('Y-m-d') . ".docx";

header('Content-Description: File Transfer');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Transfer-Encoding: binary');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Expires: 0');

$objWriter = IOFactory::createWriter($phpWord, 'Word2007');
$objWriter->save("php://output");
exit;
