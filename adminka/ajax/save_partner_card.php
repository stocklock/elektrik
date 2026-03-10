<?php
require '../functions.php';

$userId = getUserId();

$data = [
    'company_name' => $_POST['company_name'],
    'inn' => $_POST['inn'],
    'ogrnip' => $_POST['ogrnip'],
    'legal_address' => $_POST['legal_address'],
    'actual_address' => $_POST['actual_address'],
    'phone' => $_POST['phone'],
    'checking_account' => $_POST['checking_account'],
    'correspondent_account' => $_POST['correspondent_account'],
    'bik' => $_POST['bik'],
    'bank_name' => $_POST['bank_name']
];

$stmt = $pdo->prepare("SELECT id FROM partner_card WHERE user_id = ?");
$stmt->execute([$userId]);

if ($stmt->fetch()) {
    // Обновление
    $update = $pdo->prepare("UPDATE partner_card SET company_name=?, inn=?, ogrnip=?, legal_address=?, actual_address=?, phone=?, checking_account=?, correspondent_account=?, bik=?, bank_name=? WHERE user_id=?");
    $update->execute([
        $data['company_name'], $data['inn'], $data['ogrnip'], $data['legal_address'], $data['actual_address'],
        $data['phone'], $data['checking_account'], $data['correspondent_account'], $data['bik'], $data['bank_name'], $userId
    ]);
} else {
    // Вставка
    $insert = $pdo->prepare("INSERT INTO partner_card (user_id, company_name, inn, ogrnip, legal_address, actual_address, phone, checking_account, correspondent_account, bik, bank_name)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $insert->execute([
        $userId, $data['company_name'], $data['inn'], $data['ogrnip'], $data['legal_address'], $data['actual_address'],
        $data['phone'], $data['checking_account'], $data['correspondent_account'], $data['bik'], $data['bank_name']
    ]);
}

echo "Сохранено";
