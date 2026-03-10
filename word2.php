<?php
// Конфигурация базы данных
$host = 'localhost';
$db   = 'u1851662_adminka';
$user = 'u1851662_default';
$pass = '5OBw9nvRSA3Al7V0';
$charset = 'utf8mb4';
// Пример данных из базы:
$variables = [
    'company_name' => "Company",
    'inn' => "777",
	//'ogrnip' => htmlspecialchars($client['ogrnip']),
	//'legal_address' => htmlspecialchars($client['legal_address']),
	//'actual_address' => htmlspecialchars($client['actual_address']),
	//'phone' => htmlspecialchars($client['phone']),
	//'checking_account' => htmlspecialchars($client['checking_account']),
	//'correspondent_account' => htmlspecialchars($client['correspondent_account']),
	//'bik' => htmlspecialchars($client['bik']),
	//'bank_name' => htmlspecialchars($client['bank_name']),
    //'estimate_sum' => htmlspecialchars($client['estimate_sum']),
    'contract_date' => date('d.m.Y'),
];

  



// Пути
$templatePath = 'template.docx';
$tempDir = sys_get_temp_dir() . '/docx_' . uniqid();
$outputDocx = $tempDir . '/output.docx';

// Создание временной директории
mkdir($tempDir);

// Распаковка шаблона
$zip = new ZipArchive;
if ($zip->open($templatePath) === TRUE) {
    $zip->extractTo($tempDir);
    $zip->close();
} else {
    die("Не удалось открыть шаблон DOCX.");
}

// Загрузка и замена переменных в документе
$documentPath = $tempDir . '/word/document.xml';
$documentXml = file_get_contents($documentPath);
      // Заменяем плейсхолдеры
        foreach ($variables as $key => $value) {
            // Word может разбить {{place}} на {{pl}}...{{ace}}, поэтому удалим теги внутри
            $pattern = '/\{\{' . preg_quote($key, '/') . '\}\}/';
            $documentXml = preg_replace($pattern, $value, $documentXml);
            $documentXml = str_replace('{{'.$key.'}}', htmlspecialchars($value), $documentXml);
        }


// Сохраняем измененный XML обратно
file_put_contents($documentPath, $documentXml);

// Создаем новый docx-файл
$newZip = new ZipArchive;
if ($newZip->open($outputDocx, ZipArchive::CREATE) === TRUE) {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($tempDir),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $file) {
        if (!$file->isDir()) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($tempDir) + 1);
            if ($relativePath !== 'output.docx') {
                $newZip->addFile($filePath, $relativePath);
            }
        }
    }

    $newZip->close();
} else {
    die("Не удалось создать новый DOCX.");
}

// Чтение содержимого нового docx
$docxData = file_get_contents($outputDocx);

// Сохранение в MySQL
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("INSERT INTO documents (name, content, created_at) VALUES (?, ?, NOW())");
 $stmt->bindValue(1, 'Generated_' . time() . '.docx');
    $stmt->bindParam(2, $docxData, PDO::PARAM_LOB);
    $stmt->execute();

    echo "Документ успешно сохранён в базу данных.";
} catch (PDOException $e) {
    die("Ошибка базы данных: " . $e->getMessage());
}

// Очистка временных файлов (необязательно, но желательно)
function deleteDir($dir) {
    if (!file_exists($dir)) return;
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item != '.' && $item != '..') {
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            is_dir($path) ? deleteDir($path) : unlink($path);
        }
    }
    rmdir($dir);
}
deleteDir($tempDir);
?>
