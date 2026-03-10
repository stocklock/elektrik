<?php
function renderContractHTMLWithQR($inn, $phone, $service, $amount, $checkId) {
    $qr = "<img src='https://api.qrserver.com/v1/create-qr-code/?data=$checkId&size=100x100' />";
    return "
        <h1>Счёт №$checkId</h1>
        <p><strong>ИНН:</strong> $inn<br>
           <strong>Телефон:</strong> $phone<br>
           <strong>Услуга:</strong> $service<br>
           <strong>Сумма:</strong> $amount ₽</p>
        <p>QR-код чека:</p>
        $qr
        <p>Подпись самозанятого:</p>
        <img src='../signature.png' width='150'>
    ";
}

function renderContractHTML($partner, $items, $orderId, $total) {
    $date = date('d.m.Y');
    ob_start();
    ?>
    <h2 style="text-align:center;">Типовой договор №<?= $orderId ?> от <?= $date ?></h2>

    <p>г. Москва, <?= $date ?></p>

    <p><strong>Продавец:</strong> ООО "Пример", ИНН 7700000000, ОГРН 1234567890, адрес: г. Москва, ул. Пример, д. 1</p>

    <p><strong>Покупатель:</strong> <?= htmlspecialchars($partner['company_name']) ?>, ИНН: <?= $partner['inn'] ?>, 
    ОГРНИП: <?= $partner['ogrnip'] ?>, адрес: <?= $partner['legal_address'] ?></p>

    <p>1. Предмет договора: передача товаров согласно таблице.</p>

    <table border="1" cellpadding="4" cellspacing="0" width="100%">
        <tr>
            <th>№</th><th>Товар</th><th>Кол-во</th><th>Цена</th><th>Сумма</th>
        </tr>
        <?php foreach ($items as $i => $item): ?>
        <tr>
            <td><?= $i + 1 ?></td>
            <td><?= htmlspecialchars($item['name']) ?></td>
            <td><?= $item['quantity'] ?></td>
            <td><?= $item['price'] ?> ₽</td>
            <td><?= $item['total'] ?> ₽</td>
        </tr>
        <?php endforeach; ?>
        <tr>
            <td colspan="4" align="right"><strong>Итого:</strong></td>
            <td><strong><?= number_format($total, 2, ',', ' ') ?> ₽</strong></td>
        </tr>
    </table>

    <p>2. Оплата: по безналичному расчету.</p>
    <p>3. Прочие условия: стандартные.</p>
    <br><br>

    <table width="100%">
        <tr>
            <td>
                <strong>Продавец:</strong><br><br>
                ____________/Иванов И.И.<br>
            </td>
            <td>
                <strong>Покупатель:</strong><br><br>
                ____________/<?= htmlspecialchars($partner['company_name']) ?><br>
            </td>
        </tr>
    </table>
    <?php
    return ob_get_clean();
}
?>
