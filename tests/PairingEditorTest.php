<?php
require_once __DIR__ . '/../logic/PairingEditor.php';
require_once __DIR__ . '/../logic/HistoryManager.php';
require_once __DIR__ . '/../logic/CsvManager.php';

class PairingEditorTest {
    private function person($id, $driver = false, $family = null, $gender = 'M') {
        return ['id' => (string)$id, 'name' => "参加者$id", 'furigana' => '', 'family_id' => $family ?? "F$id",
            'gender' => $gender, 'is_driver' => $driver ? '1' : '0', 'nickname' => '', 'notes' => '', 'participation_count' => '0'];
    }
    private function members() {
        return [$this->person(1, true), $this->person(2), $this->person(3, true), $this->person(4), $this->person(5)];
    }
    private function assert($condition, $message) {
        if (!$condition) throw new Exception($message);
    }
    private function rejected($data, $members, $mode = 'family') {
        try {
            PairingEditor::validate($data, $members, $mode);
        } catch (InvalidArgumentException $e) {
            return;
        }
        throw new Exception('不正な配置が受理されました');
    }
    public function testSwappedArrangementSavedWithCanonicalMembers() {
        $m = $this->members();
        $data = ['cars' => [[$m[3], $m[0]], [$m[2], $m[1]]], 'walk' => [$m[4]]];
        $data['cars'][0][1]['name'] = '改ざん';
        $data['cars'][0][1]['is_driver'] = '0';
        $result = PairingEditor::validate($data, $m, 'family');
        $this->assert(array_column($result['cars'][0], 'id') === ['1', '4'], '入れ替え後の車にドライバーが先頭で保存されること');
        $this->assert($result['cars'][0][0] === $m[0], '名前とドライバー指定をサーバー側から復元すること');
        $file = tempnam(sys_get_temp_dir(), 'pair-edit');
        $csvFile = tempnam(sys_get_temp_dir(), 'pair-list');
        try {
            $history = new HistoryManager($file);
            $history->addHistory($result);
            $saved = $history->getHistory()[0];
            $this->assert($saved['cars'] === $result['cars'] && $saved['walk'] === $result['walk'], '調整後の車・徒歩を履歴に保存すること');
            $csv = new CsvManager($csvFile);
            foreach ($m as $p) $csv->add($p);
            $csv->incrementParticipationCounts(array_column($m, 'id'));
            foreach ($csv->getAll() as $p) $this->assert((int)$p['participation_count'] === 1, '全参加者の参加回数が1増えること');
        } finally {
            unlink($file);
            unlink($csvFile);
        }
    }
    public function testRejectsMissingDuplicateAndUnknownMembers() {
        $m = $this->members();
        $base = ['cars' => [[$m[0], $m[1]], [$m[2], $m[3]]], 'walk' => [$m[4]]];
        $missing = $base; $missing['walk'] = [];
        $duplicate = $base; $duplicate['walk'][] = $m[1];
        $unknown = $base; $unknown['walk'][0]['id'] = 'unknown';
        foreach ([$missing, $duplicate, $unknown] as $data) $this->rejected($data, $m);
    }
    public function testRejectsMalformedPayloads() {
        foreach ([null, 'broken', [], ['cars' => 'bad', 'walk' => []], ['cars' => [null], 'walk' => []],
            ['cars' => [], 'walk' => [['id' => []]]], ['cars' => [2 => []], 'walk' => []]] as $data) {
            $this->rejected($data, $this->members());
        }
    }
    public function testCarCapacityAndDriverRequired() {
        $m = $this->members();
        foreach ([['cars' => [$m], 'walk' => []], ['cars' => [[$m[1], $m[3]]], 'walk' => [$m[0], $m[2], $m[4]]],
            ['cars' => [[]], 'walk' => $m]] as $data) $this->rejected($data, $m);
    }
    public function testFamilyModeAndOtherModes() {
        $m = $this->members();
        $m[1]['family_id'] = $m[0]['family_id'];
        $data = ['cars' => [[$m[0], $m[3]], [$m[2], $m[1]]], 'walk' => [$m[4]]];
        $this->rejected($data, $m);
        foreach (['driver', 'random'] as $mode) $this->assert(PairingEditor::validate($data, $m, $mode) === $data, '家族を分けられるモードで有効な入れ替えが拒否されています');
        $data = ['cars' => [[$m[0]], [$m[2], $m[3]]], 'walk' => [$m[1], $m[4]]];
        $this->rejected($data, $m);
    }
    public function testDifferentFamilyMixedGenderPairRejectedInEveryMode() {
        $m = [$this->person(1, true), $this->person(2, false, null, 'F')];
        foreach (['family', 'driver', 'random'] as $mode) $this->rejected(['cars' => [$m], 'walk' => []], $m, $mode);
        $m[1]['family_id'] = $m[0]['family_id'];
        $this->assert(PairingEditor::validate(['cars' => [$m], 'walk' => []], $m, 'family')['cars'][0] === $m, '同じ家族の男女2人は許可すること');
    }
    public function testWalkOnlyAndMultipleDriversFollowExistingAlgorithm() {
        $m = $this->members();
        $this->assert(PairingEditor::validate(['cars' => [], 'walk' => $m], $m, 'family')['walk'] === $m, '徒歩だけの候補も保存できること');
        $data = ['cars' => [[$m[0], $m[2], $m[1], $m[3]]], 'walk' => [$m[4]]];
        $this->assert(count(PairingEditor::validate($data, $m, 'family')['cars'][0]) === 4, '既存の複数ドライバーの候補を許可すること');
    }
    public function testJavaScriptEditingAndEvents() {
        exec('node ' . escapeshellarg(__DIR__ . '/pairing-editor.test.cjs') . ' 2>&1', $output, $status);
        $this->assert($status === 0, "JavaScript CLIテスト失敗:\n" . implode("\n", $output));
    }

    public function testControllerSavesEditedResultAndRejectsInvalidResult() {
        // 実際のindex.phpを一時コピーでCLI実行し、本番の名簿・履歴には触れない。
        $dir = sys_get_temp_dir() . '/pair-controller-' . bin2hex(random_bytes(6));
        mkdir($dir);
        try {
            foreach (['logic', 'templates', 'public', 'data', 'data/default', 'sessions'] as $sub) mkdir("$dir/$sub");
            copy(__DIR__ . '/../index.php', "$dir/index.php");
            foreach (['logic/*.php', 'templates/*.php', 'public/style.css', 'public/pairing-editor.js'] as $pattern) {
                foreach (glob(__DIR__ . '/../' . $pattern) as $file) {
                    copy($file, "$dir/" . basename(dirname($file)) . '/' . basename($file));
                }
            }
            file_put_contents("$dir/data/workspaces.json", json_encode([['id' => 'default', 'name' => 'テスト']]));
            file_put_contents("$dir/run.php", <<<'PHP'
<?php
session_save_path(__DIR__ . '/sessions');
session_id('pairing-controller');
session_start();
$_SESSION = ['logged_in' => true, 'selected_members' => ['1', '2', '3', '4', '5'],
    'pairing_mode' => 'family', 'driver_overrides' => ['1' => true, '3' => true]];
session_write_close();
$_SERVER['REQUEST_METHOD'] = 'POST';
$_GET = ['action' => 'pairing'];
$_POST = ['decide' => '', 'pairing_result' => file_get_contents(__DIR__ . '/payload.json')];
register_shutdown_function(function () {
    file_put_contents(__DIR__ . '/session.json', json_encode($_SESSION));
});
require __DIR__ . '/index.php';
PHP
            );
            $m = $this->members();
            $csv = new CsvManager("$dir/data/default/list.csv");
            foreach ($m as $p) {
                $p['is_driver'] = '0'; // 当日の指定がマスターより優先されることも検証
                $csv->add($p);
            }
            $data = ['cars' => [[$m[0], $m[3]], [$m[2], $m[1]]], 'walk' => [$m[4]]];
            $invalid = $data;
            $invalid['cars'][0][0] = $m[1];
            file_put_contents("$dir/payload.json", json_encode($invalid));
            exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg("$dir/run.php") . ' 2>&1', $output, $status);
            $this->assert($status === 0, implode("\n", $output));
            $this->assert(strpos(implode("\n", $output), '候補を作り直しました') !== false, '不正な保存は理由を表示すること');
            $this->assert((new HistoryManager("$dir/data/default/history.json"))->getHistory() === [], '不正な変更は履歴に保存しないこと');
            foreach ($csv->getAll() as $p) $this->assert($p['participation_count'] === '0', '不正な変更で参加回数を増やさないこと');
            $session = json_decode(file_get_contents("$dir/session.json"), true);
            $this->assert(isset($session['selected_members']), '不正な保存で参加者選択を失わないこと');

            file_put_contents("$dir/payload.json", json_encode($data));
            $output = [];
            exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg("$dir/run.php") . ' 2>&1', $output, $status);
            $this->assert($status === 0 && $output === [], '有効な保存後は履歴画面に遷移すること: ' . implode("\n", $output));
            $saved = (new HistoryManager("$dir/data/default/history.json"))->getHistory()[0];
            $this->assert(array_column($saved['cars'][0], 'id') === ['1', '4'] && $saved['walk'][0]['id'] === '5', '調整後の配置を保存すること');
            $this->assert($saved['cars'][0][0]['is_driver'] === '1', '当日のドライバー指定が保存されること');
            foreach ($csv->getAll() as $p) $this->assert($p['participation_count'] === '1', '有効な保存で全員の参加回数を1増やすこと');
            $session = json_decode(file_get_contents("$dir/session.json"), true);
            $this->assert(!isset($session['selected_members']) && isset($session['flash_message']), '決定後に選択を解除し完了メッセージを設定すること');
        } finally {
            $this->removeTempDirectory($dir);
        }
    }

    private function removeTempDirectory($dir) {
        foreach (array_diff(scandir($dir), ['.', '..']) as $name) {
            $path = "$dir/$name";
            if (is_dir($path)) $this->removeTempDirectory($path); else unlink($path);
        }
        rmdir($dir);
    }
}
