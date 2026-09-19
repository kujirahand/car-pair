<?php
require_once __DIR__ . '/../logic/PairingAlgorithm.php';
// 参加者選択画面のテンプレートが「選択中」「選択候補」の2セクション構成になっていることを確認する

class SelectMembersTemplateTest {
    private $menuItems = [];

    private function assert($condition, $message) {
        if (!$condition) {
            throw new Exception($message);
        }
    }

    private function render(array $members, array $selectedIds, $pairingMode = 'family') {
        $error = '';
        $pairingModes = PairingAlgorithm::getModes();
        ob_start();
        include __DIR__ . '/../templates/select_members.php';
        $this->menuItems = $pageMenuItems ?? [];
        return ob_get_clean();
    }

    private function sampleMembers() {
        $base = ['furigana' => '', 'family_id' => 'F1', 'gender' => 'M', 'is_driver' => '0', 'nickname' => '', 'notes' => '', 'participation_count' => '1'];
        return [
            ['id' => 'a', 'name' => '甲'] + $base,
            ['id' => 'b', 'name' => '乙'] + $base,
        ];
    }

    public function testHasSelectedAndCandidateSections() {
        $html = $this->render($this->sampleMembers(), ['a']);
        $this->assert(strpos($html, 'id="selected-body"') !== false, '選択中セクションがありません');
        $this->assert(strpos($html, 'id="candidate-body"') !== false, '選択候補セクションがありません');
        $this->assert(strpos($html, 'id="selected-body"') < strpos($html, 'id="candidate-body"'), '選択中が候補より上にありません');
        $this->assert(substr_count($html, 'class="member-row"') === 2, '各メンバーが1行ずつ出力されていません');
        $this->assert(strpos($html, 'name="selected_ids[]" value="a"') !== false && preg_match('/value="a" class="member-checkbox" checked/', $html) === 1, '選択済みのチェックが維持されていません');
    }

    public function testAddFilteredButton() {
        $html = $this->render($this->sampleMembers(), []);
        $this->assert(strpos($html, 'id="add-filtered-btn"') !== false, '絞り込み結果を追加するボタンがありません');
        $this->assert(strpos($html, 'id="add-filtered-btn"') > strpos($html, 'id="candidate-heading-count"'), 'ボタンは[選択候補N人]の右にあるべきです');
        $this->assert(strpos($html, 'id="add-filtered-btn"') < strpos($html, 'id="candidate-empty"'), 'ボタンは選択候補の見出し行にあるべきです');
        $this->assert(strpos($html, 'id="add-filtered-count"') !== false && strpos($html, '人を追加</button>') !== false, 'ボタンの文言が「以下のN人を追加」になっていません');
        $this->assert(preg_match('/id="add-filtered-btn"[^>]*name=/', $html) === 0, 'ボタンの値がフォーム送信されてはいけません');
    }

    public function testEmptyRosterMessage() {
        $html = $this->render([], []);
        $this->assert(strpos($html, '名簿がありません') !== false, '名簿が空のときのメッセージがありません');
    }

    public function testPairingModeSelectBox() {
        $html = $this->render($this->sampleMembers(), [], 'driver');
        $this->assert(strpos($html, 'name="pairing_mode"') !== false, 'アルゴリズム選択ボックスがありません');
        foreach (['家族を一緒に', 'ドライバー中心', '完全ランダム'] as $label) {
            $this->assert(strpos($html, '>' . $label . '</option>') !== false, "選択肢 $label がありません");
        }
        $this->assert(preg_match('/<option value="driver" selected>/', $html) === 1, '保存されているモードが選択状態になっていません');
        $this->assert(strpos($html, 'name="pairing_mode"') > strpos($html, 'id="candidate-body"'), '選択ボックスは画面下部にあるべきです');
    }

    public function testSelectedVisibilityToggle() {
        $html = $this->render($this->sampleMembers(), ['a']);
        $this->assert(strpos($html, 'id="toggle-selected"') !== false, '選択中の表示/非表示スイッチがありません');
        $pos = strpos($html, 'id="toggle-selected"');
        $this->assert($pos > strpos($html, 'id="selected-heading-count"'), 'スイッチは[選択中N人]の右にあるべきです');
        $this->assert($pos < strpos($html, 'id="selected-empty"'), 'スイッチは選択中の見出し行にあるべきです');
        $this->assert($pos > strpos($html, 'id="member-search"'), 'スイッチは検索ボックスの下にあるべきです');
        $this->assert(strpos($html, '#selected-body.is-hidden') !== false, '非表示用のスタイルがありません');
        $this->assert(preg_match('/id="toggle-selected"[^>]*name=/', $html) === 0, 'スイッチの値がフォーム送信されてはいけません');
    }

    public function testDriverToggleSwitch() {
        $members = $this->sampleMembers();
        $members[0]['is_driver'] = '1';
        $html = $this->render($members, []);
        $this->assert(substr_count($html, 'class="driver-checkbox"') === 2, '各メンバーにドライバー切り替えスイッチが必要です');
        $this->assert(substr_count($html, 'class="switch-track"') === 3, 'ドライバー2件+選択中表示の計3つのスイッチが必要です');
        $this->assert(preg_match('/name="is_driver\[a\]" value="1" checked class="driver-checkbox"/', $html) === 1, 'ドライバーのチェック状態が維持されていません');
        $this->assert(strpos($html, '🚗 運転') !== false && strpos($html, '👤 乗客') !== false, '運転/乗客のラベルがありません');
    }

    public function testGenderByTextColorClass() {
        $members = $this->sampleMembers();
        $members[1]['gender'] = 'F';
        $html = $this->render($members, []);
        $this->assert(strpos($html, 'class="member-name man">甲') !== false, '男性の名前に .man が付いていません');
        $this->assert(strpos($html, 'class="member-name woman">乙') !== false, '女性の名前に .woman が付いていません');
        $this->assert(strpos($html, 'badge-gender') === false, '男女バッジは表示しないこと');
        $this->assert(strpos($html, '>男<') === false && strpos($html, '>女<') === false, '男女の文字表記は省略すること');
    }

    public function testFuriganaIsSearchOnly() {
        $members = $this->sampleMembers();
        $members[0]['furigana'] = 'こうさん';
        $html = $this->render($members, []);
        $this->assert(strpos($html, 'data-sort="furigana"') === false, 'ふりがな列の見出しは表示しないこと');
        $this->assert(strpos($html, 'class="furigana-cell"') !== false && strpos($html, 'こうさん') !== false, '検索用にふりがなはDOMに残すこと');
        $this->assert(strpos($html, '.table td.furigana-cell') !== false, 'ふりがなを非表示にするスタイルがありません');
    }

    public function testScreenshotButtonRemoved() {
        $html = $this->render($this->sampleMembers(), []);
        $this->assert(strpos($html, 'select_by_screenshot') === false, 'スクショ選択のリンクが残っています');
        $this->assert(strpos(json_encode($this->menuItems), 'select_by_textbox') !== false, 'テキストから追加は残すこと');
    }

    public function testActionsMovedToHamburgerMenu() {
        $html = $this->render($this->sampleMembers(), []);
        $ids = array_column($this->menuItems, 'id');
        $this->assert(in_array('clear-all-btn', $ids, true), '全部クリアがメニュー項目にありません');
        $this->assert(strpos($html, 'id="clear-all-btn"') === false, '全部クリアが画面本体に残っています');
        $this->assert(strpos($html, '📝 テキストから追加') === false, 'テキストから追加が画面本体に残っています');
    }
}
