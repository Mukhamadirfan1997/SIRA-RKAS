<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\RichText\RichText;

function getCellValue($cell)
{
    $value = $cell->getValue();
    if ($value instanceof RichText) {
        return $value->getPlainText();
    }
    return $value;
}

$spreadsheet = IOFactory::load(__DIR__ . '/PRD/RKAS PERTAHAP SETELAH PERGESERAN edit.xlsx');
$worksheet = $spreadsheet->getActiveSheet();
$rows = [];

foreach ($worksheet->getRowIterator() as $row) {
    $cellIterator = $row->getCellIterator();
    $cellIterator->setIterateOnlyExistingCells(false);
    $cells = [];
    foreach ($cellIterator as $cell) {
        $cells[] = trim(getCellValue($cell) ?? '');
    }
    $rows[] = $cells;
}

echo "=== First 20 Rows of Excel File ===\n";
for ($i = 0; $i < min(20, count($rows)); $i++) {
    echo "Row " . ($i + 1) . ":\n";
    print_r($rows[$i]);
    echo "\n";
}
