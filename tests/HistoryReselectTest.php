<?php
require_once __DIR__ . '/../logic/HistoryManager.php';
// 履歴画面の「このメンバーで再び選ぶ」ボタンと、履歴から選択状態を復元するロジックを確認する

class HistoryReselectTest {
    private function assert($condition, $message) {
        if (!$condition) {
            throw new Exception($message);
        }
    }

    private function person($id, $driver) {
        return ['id' => (string)$id, 'name' => "P$id", 'family_id' => 'F', 'gender' => 'M', 'is_driver' => $driver ? '1' : '0'];
    }

    private function record($date = '2026-01-01 10:00:00') {
        return [
            'date' => $date,
            'cars' => [[$this->person(1, true), $this->person(2, false)], [$this->person(3, true)]],
            'walk' => [$this->person(4, false)],
        ];
    }

    private function renderHistory($latest, array $past = []) {
        $pastHistories = $past;
        ob_start();
        include __DIR__ . '/../templates/history.php';
        return ob_get_clean();
    }

    public function testExtractSelectionIncludesCarsAndWalkers() {
        $sel = HistoryManager::extractSelection($this->record());
        $this->assert($sel['ids'] === ['1', '2', '3', '4'], '車と徒歩の全員のIDが必要です');
        $this->assert($sel['drivers'] === ['1' => true, '2' => false, '3' => true, '4' => false], 'ドライバー指定が履歴どおりではありません');
    }

    public function testExtractSelectionWithoutWalkAndDuplicates() {
        $record = ['date' => 'x', 'cars' => [[$this->person(1, true), $this->person(1, false)]]];
        $sel = HistoryManager::extractSelection($record);
        $this->assert($sel['ids'] === ['1'], '徒歩なしでも動き、重複IDは1つにすること');
        $this->assert(HistoryManager::extractSelection([])['ids'] === [], '空の履歴でも動くこと');
    }

    public function testFindByDate() {
        $file = tempnam(sys_get_temp_dir(), 'hist');
        $hm = new HistoryManager($file);
        file_put_contents($file, json_encode([$this->record('A'), $this->record('B')]));
        $this->assert($hm->findByDate('B')['date'] === 'B', '日時で履歴を探せません');
        $this->assert($hm->findByDate('nothing') === null, '見つからないときは null');
        unlink($file);
    }

    public function testButtonOnLatestAndPastCards() {
        $html = $this->renderHistory($this->record('2026-01-02 10:00:00'), [$this->record('2026-01-01 10:00:00'), $this->record('2025-12-31 10:00:00')]);
        $this->assert(substr_count($html, '🔁 このメンバーで再び選ぶ') === 3, '最新1件+過去2件の各カードにボタンが必要です');
        $this->assert(substr_count($html, 'action="?action=reselect" method="post"') === 3, 'POSTで送信すること');
        foreach (['2026-01-02 10:00:00', '2026-01-01 10:00:00', '2025-12-31 10:00:00'] as $date) {
            $this->assert(strpos($html, 'name="history_date" value="' . $date . '"') !== false, "$date のボタンに日時が渡されていません");
        }
    }

    public function testButtonIsSmallAndBottomRight() {
        $html = $this->renderHistory($this->record());
        $this->assert(strpos($html, 'class="btn btn-outline btn-sm">🔁') !== false, '小さめのボタン(btn-sm)であること');
        $css = file_get_contents(__DIR__ . '/../public/style.css');
        $this->assert(preg_match('/\.reselect-form \{[^}]*justify-content: flex-end/s', $css) === 1, 'ボタンは右寄せであるべきです');
        // 最新カード内で、車一覧より後ろ(右下)にある
        $this->assert(strpos($html, 'class="reselect-form"') > strpos($html, 'class="cars-grid"'), 'ボタンはカードの下部にあるべきです');
    }

    public function testNoButtonWhenNoHistory() {
        $html = $this->renderHistory(null);
        $this->assert(strpos($html, 'reselect') === false, '履歴がないときはボタンを出さないこと');
    }
}
