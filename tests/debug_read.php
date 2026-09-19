<?php
require_once __DIR__ . '/../logic/CsvManager.php';
$csv = new CsvManager(__DIR__ . '/../data/default/list.csv');
$id = $csv->add([
    'name' => '名無ゴン',
    'furigana' => 'ななしごん',
    'family_id' => 'テスト家',
    'gender' => 'M',
    'is_driver' => '0',
    'nickname' => '',
    'notes' => '',
    'participation_count' => '0'
]);

$all = $csv->getAll();
$found = false;
foreach ($all as $m) {
    if ($m['id'] === $id) {
        $found = true;
    }
}
echo "Found added user (with empty optional fields): " . ($found ? "YES" : "NO") . "\n";
$last = end($all);
echo "Count of columns read for last: " . count($last) . "\n";
print_r($last);
