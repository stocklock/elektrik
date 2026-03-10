<?php
// Параметры подключения
$host = 'localhost';
$db   = 'u1851662_adminka';
$user = 'u1851662_default';
$pass = 'pass';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];
// Повышение цен
/*
try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // Обновляем цены, увеличивая на 23%
    $sql = "UPDATE prices SET price = price * 1.23";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    echo "Цены успешно обновлены на 23%.";

} catch (PDOException $e) {
    echo "Ошибка подключения или выполнения запроса: " . $e->getMessage();
}
*/


try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // Округляем цены до целого без изменения величины
    $sql = "UPDATE prices SET price = ROUND(price)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    echo "Цены успешно округлены до целого числа.";

} catch (PDOException $e) {
    echo "Ошибка: " . $e->getMessage();
}

