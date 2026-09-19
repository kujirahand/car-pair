<?php
session_start();
$_SESSION['logged_in'] = true;
$_SESSION['workspace_id'] = 'default';
$_GET['action'] = 'select_members';
$_SERVER['REQUEST_METHOD'] = 'GET';

ob_start();
require __DIR__ . '/../index.php';
$html = ob_get_clean();

$lines = explode("\n", $html);
foreach ($lines as $i => $line) {
    if (strpos($line, '名無ゴン') !== false) {
        echo "Line $i: " . trim($line) . "\n";
    }
}
