<?php
require_once __DIR__ . '/../logic/CsvManager.php';
require_once __DIR__ . '/../logic/PairingAlgorithm.php';

$csv = new CsvManager(__DIR__ . '/../data/ws_8e5d4f80/list.csv');
$members = $csv->getByIds([56]);

$history = [];
$pairingAlg = new PairingAlgorithm();
$result = $pairingAlg->generate($members, $history);

print_r($result);
