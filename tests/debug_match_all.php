<?php
require_once __DIR__ . '/../logic/CsvManager.php';
require_once __DIR__ . '/../logic/SelectByScreenshot.php';

$logic = new SelectByScreenshot();

foreach (['default', 'ws_8e5d4f80'] as $ws) {
    echo "Workspace: $ws\n";
    $csv = new CsvManager(__DIR__ . "/../data/$ws/list.csv");
    $members = $csv->getAll();
    $matchedIds = $logic->matchMembers(["ななし"], $members);
    echo "Matches for 'ななし': " . count($matchedIds) . "\n";
    foreach ($matchedIds as $id) {
        foreach ($members as $mem) {
            if ($mem['id'] === $id) {
                print_r($mem);
            }
        }
    }
}
