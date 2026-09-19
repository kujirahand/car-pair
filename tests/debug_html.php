<?php
session_start();
$_SESSION['workspace_id'] = 'default';
require_once __DIR__ . '/../logic/WorkspaceManager.php';
require_once __DIR__ . '/../logic/CsvManager.php';

$workspaceManager = new WorkspaceManager();
$workspacePaths = $workspaceManager->getWorkspacePaths('default');
$csv = new CsvManager($workspacePaths['list']);
$members = $csv->getAll();

$matchedRows = 0;
foreach ($members as $m) {
    if (strpos($m['name'], 'ななし') !== false || 
        strpos($m['furigana'], 'ななし') !== false ||
        strpos($m['nickname'], 'ななし') !== false ||
        strpos($m['notes'], 'ななし') !== false) {
        $matchedRows++;
        echo "Match: " . json_encode($m, JSON_UNESCAPED_UNICODE) . "\n";
    }
}
echo "Total Matches: $matchedRows\n";
