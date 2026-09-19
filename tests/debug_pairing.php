<?php
require_once __DIR__ . '/../logic/CsvManager.php';
require_once __DIR__ . '/../logic/PairingAlgorithm.php';

$csv = new CsvManager(__DIR__ . '/../data/default/list.csv');

// Simulate the exact session state the user could have
// what if they bypassed select_members somehow? Or what if there WAS a duplicate in $_SESSION?
$_SESSION['selected_members'] = [57, 57]; // Duplicate!

$selectedIds = $_SESSION['selected_members'];

$members = $csv->getByIds($selectedIds);

echo "Members count retrieved: " . count($members) . "\n";
print_r($members);

$history = [];
$pairingAlg = new PairingAlgorithm();
$result = $pairingAlg->generate($members, $history);

echo "Pairing result:\n";
print_r($result);
