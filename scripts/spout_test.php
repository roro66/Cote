<?php
require __DIR__ . '/../vendor/autoload.php';

use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use OpenSpout\Common\Entity\Row as SpoutRow;

$labels = ['enero 2025','febrero 2025','marzo 2025'];
$rows = [];
foreach ($labels as $i => $label) {
    $rows[] = [$label, $i * 1000];
}

$out = __DIR__ . '/../storage/app/test-spout.xlsx';
@unlink($out);
$writer = new XlsxWriter();
$writer->openToFile($out);
$writer->addRow(SpoutRow::fromValues(['Mes', 'Total (CLP)']));
foreach ($rows as $r) {
    $writer->addRow(SpoutRow::fromValues($r));
}
$writer->close();

echo "Wrote: $out\n";
