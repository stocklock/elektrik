<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Твои данные сметы
$products = [
    [
        "product_name" => "Монтаж системы",
        "product_qty" => 3,
        "product_price" => 1500,
        "product_code" => 101,
        "product_color" => "шт.",
        "product_size" => 1500
    ],
    [
        "product_name" => "Прокладка кабеля",
        "product_qty" => 10,
        "product_price" => 200,
        "product_code" => 102,
        "product_color" => "м.",
        "product_size" => 200
    ],
    // Добавь свои данные здесь
];

// Путь к шаблону Excel
$templateFile = 'template_smeta.xlsx';

// Загружаем шаблон
$spreadsheet = IOFactory::load($templateFile);
$sheet = $spreadsheet->getActiveSheet();

// Стартовая строка для вывода (например, 5-я, если первые 4 строки — шапка)
$startRow = 5;

foreach ($products as $index => $product) {
    $row = $startRow + $index;

    // № п/п
    $sheet->setCellValue("A$row", $index + 1);

    // Наименование работ
    $sheet->setCellValue("B$row", $product["product_name"]);

    // Количество
    $sheet->setCellValue("C$row", $product["product_qty"]);

    // Единица измерения (product_color)
    $sheet->setCellValue("D$row", $product["product_color"]);

    // Стоимость единицы (product_size)
    $sheet->setCellValue("E$row", $product["product_size"]);

    // Сумма = количество * стоимость единицы
    $sum = $product["product_qty"] * $product["product_size"];
    $sheet->setCellValue("F$row", $sum);

    // product_code не выводим, но можно использовать в дальнейшем, если нужно
}

// Можно добавить общую сумму внизу
$summaryRow = $startRow + count($products);
$sheet->setCellValue("E$summaryRow", "Итого:");
$sheet->setCellValue("F$summaryRow", "=SUM(F$startRow:F" . ($summaryRow - 1) . ")");

// Сохраняем файл
$outputFile = 'estimate_result.xlsx';
$writer = new Xlsx($spreadsheet);
$writer->save($outputFile);

echo "Смета сохранена в $outputFile\n";
