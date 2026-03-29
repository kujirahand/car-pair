<?php
session_start();

require_once __DIR__ . '/logic/Auth.php';
require_once __DIR__ . '/logic/CsvManager.php';
require_once __DIR__ . '/logic/HistoryManager.php';
require_once __DIR__ . '/logic/PairingAlgorithm.php';
require_once __DIR__ . '/logic/SelectByScreenshot.php';

$auth = new Auth();
$action = isset($_GET['action']) ? $_GET['action'] : 'select_members';
$contentView = '';

if ($action === 'login') {
    $error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($auth->login($_POST['username'] ?? '', $_POST['password'] ?? '')) {
            header('Location: ?action=select_members');
            exit;
        } else {
            $error = 'ユーザー名またはパスワードが間違っています。';
        }
    }
    $contentView = 'login.php';
    require __DIR__ . '/templates/layout.php';
    exit;
}

if ($action === 'logout') {
    $auth->logout();
    header('Location: ?action=login');
    exit;
}

// Redirect to login if not authenticated
if (!$auth->isLoggedIn()) {
    header('Location: ?action=login');
    exit;
}

$csv = new CsvManager();

if ($action === 'export_csv') {
    $file = __DIR__ . '/data/list.csv';
    if (file_exists($file)) {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="list.csv"');
        readfile($file);
    }
    exit;
}

if ($action === 'edit_list') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['add'])) {
            $csv->add([
                'name' => $_POST['name'],
                'furigana' => $_POST['furigana'],
                'family_id' => $_POST['family_id'],
                'gender' => $_POST['gender'],
                'is_driver' => isset($_POST['is_driver']) ? '1' : '0',
                'nickname' => $_POST['nickname'] ?? '',
                'notes' => $_POST['notes'] ?? '',
                'participation_count' => '0'
            ]);
        } elseif (isset($_POST['edit'])) {
            $csv->update($_POST['id'], [
                'name' => $_POST['name'],
                'furigana' => $_POST['furigana'] ?? '',
                'family_id' => $_POST['family_id'],
                'gender' => $_POST['gender'],
                'is_driver' => isset($_POST['is_driver']) ? '1' : '0',
                'nickname' => $_POST['nickname'] ?? '',
                'notes' => $_POST['notes'] ?? ''
            ]);
        } elseif (isset($_POST['delete'])) {
            $csv->delete($_POST['id']);
        } elseif (isset($_POST['import_replace']) || isset($_POST['import_delta'])) {
            if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
                $isReplace = isset($_POST['import_replace']);
                $csv->import($_FILES['csv_file']['tmp_name'], $isReplace);
            }
        }
        header('Location: ?action=edit_list');
        exit;
    }
    $members = $csv->getAll();
    $contentView = 'edit_list.php';
    require __DIR__ . '/templates/layout.php';
    exit;
}

if ($action === 'select_members') {
    $error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $selectedIds = isset($_POST['selected_ids']) ? $_POST['selected_ids'] : [];
        if (!empty($selectedIds)) {
            $_SESSION['selected_members'] = $selectedIds;
            header('Location: ?action=pairing');
            exit;
        } else {
            $error = "少なくとも1人を選択してください。";
        }
    }
    
    $members = $csv->getAll();
    usort($members, function($a, $b) {
        return (int)$b['participation_count'] <=> (int)$a['participation_count'];
    });
    $selectedIds = $_SESSION['selected_members'] ?? [];
    $contentView = 'select_members.php';
    require __DIR__ . '/templates/layout.php';
    exit;
}

if ($action === 'select_by_screenshot') {
    $error = '';
    $step = 'upload';
    $extractedText = '';
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $step = $_POST['step'] ?? 'upload';
        $logic = new SelectByScreenshot();
        
        if ($step === 'confirm') {
            $text = trim($_POST['extracted_text'] ?? '');
            $extractedNames = array_filter(array_map('trim', explode("\n", $text)));
            
            $members = $csv->getAll();
            $matchedIds = $logic->matchMembers($extractedNames, $members);
            
            $_SESSION['selected_members'] = $matchedIds;
            $_SESSION['flash_message'] = count($matchedIds) . ' 人のメンバーをリストから抽出・選択しました！';
            header('Location: ?action=select_members');
            exit;
        } else {
            if (!isset($_FILES['screenshot']) || $_FILES['screenshot']['error'] !== UPLOAD_ERR_OK) {
                $error = '画像のアップロードに失敗しました。';
            } else {
                try {
                    $extractedNames = $logic->extractNamesFromImage($_FILES['screenshot']['tmp_name']);
                    $extractedText = implode("\n", $extractedNames);
                    $step = 'confirm';
                } catch (Exception $e) {
                    $error = $e->getMessage();
                }
            }
        }
    }
    
    $contentView = 'select_by_screenshot.php';
    require __DIR__ . '/templates/layout.php';
    exit;
}

if ($action === 'pairing') {
    $selectedIds = isset($_SESSION['selected_members']) ? $_SESSION['selected_members'] : [];
    if (empty($selectedIds)) {
        header('Location: ?action=select_members');
        exit;
    }

    $members = $csv->getByIds($selectedIds);
    $historyManager = new HistoryManager();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['decide'])) {
        // Decide was clicked
        if (!empty($_POST['pairing_result'])) {
            $pairingJson = $_POST['pairing_result'];
            $historyManager->addHistory(json_decode($pairingJson, true));
            $csv->incrementParticipationCounts($selectedIds);
            unset($_SESSION['selected_members']);
            $_SESSION['flash_message'] = '組合せを保存しました！';
            header('Location: ?action=history');
            exit;
        }
    }

    // Generate pairing candidates
    $startTime = microtime(true);
    $pairingAlg = new PairingAlgorithm();
    $result = $pairingAlg->generate($members, $historyManager->getHistory());
    $executionTime = round(($endTime = microtime(true)) - $startTime, 4) * 1000; // in milliseconds
    $contentView = 'pairing.php';
    require __DIR__ . '/templates/layout.php';
    exit;
}

if ($action === 'history') {
    $historyManager = new HistoryManager();
    $allHistory = $historyManager->getHistory();
    // sort history by descending (latest first). The file appends to the end, so reverse it.
    $reversedHistory = array_reverse($allHistory);
    $latest = !empty($reversedHistory) ? $reversedHistory[0] : null;
    $pastHistories = array_slice($reversedHistory, 1, 5); // Up to 5 past items

    $contentView = 'history.php';
    require __DIR__ . '/templates/layout.php';
    exit;
}

header('Location: ?action=select_members');
