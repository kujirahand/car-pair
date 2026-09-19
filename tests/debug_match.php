<?php
require_once __DIR__ . '/../logic/CsvManager.php';
require_once __DIR__ . '/../logic/SelectByScreenshot.php';

$csv = new CsvManager(__DIR__ . '/../data/default/list.csv');
$members = $csv->getAll();

$logic = new SelectByScreenshot();
$matchedIds = $logic->matchMembers(["ななし"], $members);

echo "Matched IDs:\n";
foreach ($matchedIds as $id) {
    foreach ($members as $m) {
        if ($m['id'] === $id) {
            echo "ID: $id, Name: {$m['name']}, Furi: {$m['furigana']}, Nick: {$m['nickname']}\n";
        }
    }
}
