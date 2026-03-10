<?php
// Подключение к базе (пример для MySQL)
$host = 'localhost';
$db   = 'u1851662_adminka';
$user = 'u1851662_default';
$pass = '5OBw9nvRSA3Al7V0';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // чтобы видеть ошибки
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    exit('Ошибка подключения к базе: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8" />
<title>Смета с поиском услуг из таблицы prices</title>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<style>
  #search-results {
    border: 1px solid #ccc;
    max-width: 400px;
    position: absolute;
    background: white;
    z-index: 1000;
  }
  #search-results div {
    padding: 6px;
    cursor: pointer;
  }
  #search-results div:hover {
    background-color: #f0f0f0;
  }
  table {
    border-collapse: collapse;
    margin-top: 20px;
    width: 60%;
  }
  th, td {
    border: 1px solid #ccc;
    padding: 8px;
  }
</style>
</head>
<body>

<h2>Поиск услуги</h2>
<input type="text" id="service-search" autocomplete="off" placeholder="Введите название услуги" style="width: 400px;">
<div id="search-results"></div>

<h2>Смета</h2>
<table id="estimate-table">
  <thead>
    <tr>
      <th>Услуга</th><th>Кол-во</th><th>Ед.</th><th>Цена за ед.</th><th>Сумма</th><th>Действия</th>
    </tr>

  </thead>
  <tbody></tbody>
</table>



<script>
let estimateItems = [];

function renderEstimate() {
  let tbody = $('#estimate-table tbody');
  tbody.empty();
  estimateItems.forEach((item, i) => {
    let sum = item.price * item.quantity;
    tbody.append(`<tr>
      <td>${item.title}</td>
      <td><input type="number" min="1" value="${item.quantity}" data-index="${i}" class="edit-qty" style="width: 50px"></td>
      <td>${item.ed}</td>
      <td>${item.price.toFixed(2)}</td>
      <td>${sum.toFixed(2)}</td>
      <td><button data-index="${i}" class="delete-item">Удалить</button></td>
    </tr>`);
  });
}

// Обработчик поиска
$('#service-search').on('input', function() {
  let q = $(this).val().trim();
  if (q.length < 2) {
    $('#search-results').empty();
    return;
  }
  $.ajax({
    url: 'search_services.php',
    method: 'GET',
    data: {q},
    success: function(data) {
      // data — JSON уже, если правильно настроено
      let html = '';
      data.forEach(service => {
        html += `<div data-id="${service.id}" data-title="${service.title}" data-ed="${service.ed}" data-price="${service.price}">
          ${service.title} — ${service.price} ₽ / ${service.ed}
        </div>`;
      });
      $('#search-results').html(html);
    },
    error: function() {
      $('#search-results').empty();
    }
  });
});

// Добавление услуги в смету по клику
$('#search-results').on('click', 'div', function() {
  let id = $(this).data('id');
  let title = $(this).data('title');
  let ed = $(this).data('ed');
  let price = parseFloat($(this).data('price'));
  let quantity = parseInt($('#search-quantity').val());
  if(quantity < 1) quantity = 1;

  let existing = estimateItems.find(i => i.id == id);
  if (existing) {
    existing.quantity += quantity;
  } else {
    estimateItems.push({id, title, ed, price, quantity});
  }
  renderEstimate();
  $('#search-results').empty();
  $('#service-search').val('');
  $('#search-quantity').val(1);
});

// Изменение количества в смете
$('#estimate-table').on('change', '.edit-qty', function() {
  let index = $(this).data('index');
  let val = parseInt($(this).val());
  if(val < 1) val = 1;
  estimateItems[index].quantity = val;
  renderEstimate();
});

// Удаление из сметы
$('#estimate-table').on('click', '.delete-item', function() {
  let index = $(this).data('index');
  estimateItems.splice(index, 1);
  renderEstimate();
});	



</script>
<h3>Выберите клиента</h3>
<select id="partner-card-select">
  <?php
  $stmt = $pdo->query("SELECT id, company_name, inn FROM partner_card ORDER BY company_name");
  $partners = $stmt->fetchAll();
  if ($partners) {
    foreach ($partners as $partner) {
      // Экранируем вывод htmlspecialchars для безопасности
      $id = htmlspecialchars($partner['id']);
      $name = htmlspecialchars($partner['company_name']);
      $inn = htmlspecialchars($partner['inn']);
      echo "<option value='{$id}'>{$name} (ИНН: {$inn})</option>";
    }
  } else {
    echo "<option disabled>Клиенты не найдены</option>";
  }
  ?>
</select>
<br>
	<br>
	<button id="save-estimate">Сохранить смету и создать договор</button>

<div id="contract-result" style="margin-top: 20px; border: 1px solid #ccc; padding: 10px;"></div>
<script>
	$('#save-estimate').click(() => {
  const partnerCardId = parseInt($('#partner-card-select').val());
  if (!partnerCardId) {
    alert('Выберите клиента');
    return;
  }

  if (estimateItems.length === 0) {
    alert('Смета пуста');
    return;
  }

  $.ajax({
    url: 'save_estimate.php',
    method: 'POST',
    contentType: 'application/json',
    data: JSON.stringify({
      partner_card_id: partnerCardId,
      estimateItems: estimateItems
    }),
    success: function(response) {
      if (response.contract) {
        $('#contract-result').html('<h3>Сгенерированный договор</h3><pre>' + response.contract + '</pre>');
        estimateItems = [];
        renderEstimate();
		  $client=response.clientid
		  
      } else {
        alert('Ошибка: ' + (response.error || 'Неизвестная'));
      }
    },
    error: function() {
      alert('Ошибка при сохранении сметы');
    }
  });
});
	</script>
	</body>
</html>