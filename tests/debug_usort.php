<?php
require_once __DIR__ . '/../logic/CsvManager.php';
$csv = new CsvManager(__DIR__ . '/../data/default/list.csv');
$members = $csv->getAll();
echo "Before usort: " . count($members) . "\n";
usort($members, function($a, $b) {
    return (int)$b['participation_count'] <=> (int)$a['participation_count'];
});
echo "After usort: " . count($members) . "\n";
print_r(array_slice($members, -3));
