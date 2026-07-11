<?php
require 'vendor/autoload.php';

$file = 'PRD/REFRENSI KODE RKAS.xlsx';
$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($file);
$reader->setReadDataOnly(true);
$spreadsheet = $reader->load($file);

$out = fopen('prd_output_utf8.txt', 'a');

$sheet = $spreadsheet->getSheet(1); // Sheet 2
fwrite($out, PHP_EOL . "Active Sheet: " . $sheet->getTitle() . PHP_EOL);
fwrite($out, "Max Row: " . $sheet->getHighestRow() . PHP_EOL);
fwrite($out, "Max Col: " . $sheet->getHighestColumn() . PHP_EOL);

fwrite($out, PHP_EOL . "--- Header Row 1 ---" . PHP_EOL);
foreach ($sheet->getRowIterator(1, 1) as $row) {
    foreach ($row->getCellIterator() as $cell) {
        fwrite($out, $cell->getColumn() . ': [' . $cell->getValue() . ']' . PHP_EOL);
    }
}

fwrite($out, PHP_EOL . "--- Sample Row 2 ---" . PHP_EOL);
foreach ($sheet->getRowIterator(2, 2) as $row) {
    foreach ($row->getCellIterator() as $cell) {
        fwrite($out, $cell->getColumn() . ': [' . $cell->getValue() . ']' . PHP_EOL);
    }
}
fclose($out);
