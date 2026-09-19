<?php
require_once __DIR__ . '/../logic/CsvManager.php';
$csv = new CsvManager(__DIR__ . '/../data/default/list.csv');
$members = $csv->getAll();
foreach ($members as $m) {
    $str = $m['name'].$m['furigana'].$m['family_id'].$m['nickname'].$m['notes'];
    if (strpos($str, 'ななし') !== false || strpos($str, '名無') !== false) {
        echo "MATCH: {$m['id']} - {$m['name']}\n";
    }
}
