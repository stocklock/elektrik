<?php
// Конфигурация базы данных
$host = 'localhost';
$db   = 'db';
$user = 'user';
$pass = 'pass';
$charset = 'utf8mb4';

// ID документа, который хотим скачать (например, из GET-параметра)
$docId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($docId <= 0) {
    die('Некорректный ID документа.');
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Получаем имя файла и содержимое
    $stmt = $pdo->prepare("SELECT name, content FROM documents WHERE id = ?");
    $stmt->execute([$docId]);
    $document = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$document) {
        die('Документ не найден.');
    }

    // Отдаем файл пользователю с правильными заголовками
    header('Content-Description: File Transfer');
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="' . basename($document['name']) . '"');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . strlen($document['content']));

    echo $document['content'];
    exit;

} catch (PDOException $e) {
    die("Ошибка базы данных: " . $e->getMessage());
}
?>
