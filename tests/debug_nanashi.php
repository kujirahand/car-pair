<?php
require_once __DIR__ . '/../logic/CsvManager.php';

$csv1 = new CsvManager(__DIR__ . '/../data/default/list.csv');
$all1 = $csv1->getAll();
$count1 = 0;
foreach ($all1 as $m) {
    if (strpos(json_encode($m, JSON_UNESCAPED_UNICODE), 'ななし') !== false) {
        $count1++;
    }
}
echo "Default: $count1\n";

$csv2 = new CsvManager(__DIR__ . '/../data/ws_8e5d4f80/list.csv');
$all2 = $csv2->getAll();
$count2 = 0;
foreach ($all2 as $m) {
    if (strpos(json_encode($m, JSON_UNESCAPED_UNICODE), 'ななし') !== false) {
        $count2++;
    }
}
echo "WS2: $count2\n";
