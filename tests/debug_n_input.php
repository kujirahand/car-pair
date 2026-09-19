<?php
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['member_text'] = 'ななし';
$_GET['action'] = 'select_by_textbox';

// We need to trace index.php exactly.
// Let's create an isolated trace output
require_once __DIR__ . '/../logic/CsvManager.php';
require_once __DIR__ . '/../logic/SelectByScreenshot.php';

$csv = new CsvManager(__DIR__ . '/../data/default/list.csv');
$members = $csv->getAll();

$names = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $_POST['member_text'])));
$logic = new SelectByScreenshot();
$matchedIds = $logic->matchMembers($names, $members);

echo "Matched IDs: " . implode(', ', $matchedIds) . "\n";
foreach ($matchedIds as $id) {
    foreach ($members as $m) {
        if ($m['id'] === $id) {
            echo "- " . $m['name'] . "\n";
        }
    }
}
