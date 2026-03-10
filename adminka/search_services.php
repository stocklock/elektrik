<?php
header('Content-Type: application/json');
$pdo = new PDO('mysql:host=localhost;dbname=db;charset=utf8', 'user', 'pass');

$q = $_GET['q'] ?? '';
$q = trim($q);

if (strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("SELECT id, title, ed, price FROM prices WHERE title LIKE ? ORDER BY title");
$stmt->execute(['%' . $q . '%']);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($results);
