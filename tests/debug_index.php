<?php
require_once __DIR__ . '/../logic/CsvManager.php';
require_once __DIR__ . '/../logic/WorkspaceManager.php';

// Simulate what index.php does on edit_list
$workspaceManager = new WorkspaceManager(__DIR__ . '/../data');
$currentWorkspaceId = 'default';
$workspacePaths = $workspaceManager->getWorkspacePaths($currentWorkspaceId);
$csv = new CsvManager($workspacePaths['list']);

echo "Current CSV path: " . $workspacePaths['list'] . "\n";

// Add a test user
$item = [
    'name' => 'Web追加太郎',
    'furigana' => 'うぇぶたろう',
    'family_id' => 'Web家',
    'gender' => 'M',
    'is_driver' => '1',
    'nickname' => '',
    'notes' => '',
    'participation_count' => '0'
];

$id = $csv->add($item);
echo "Added user with ID: $id\n";

// Now simulate index.php reading properties
$members = $csv->getAll();
$found = array_filter($members, fn($m) => $m['id'] == $id);
if (!empty($found)) {
    echo "User found in getAll()\n";
} else {
    echo "USER NOT FOUND IN getAll()!!!\n";
}
