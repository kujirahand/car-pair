<?php
// 乗りあわせ候補画面のテンプレートとコンパクト表示用CSSを確認する

class PairingTemplateTest {
    private function assert($condition, $message) {
        if (!$condition) {
            throw new Exception($message);
        }
    }

    private function person($name, $driver, $gender = 'M') {
        return ['id' => $name, 'name' => $name, 'family_id' => 'F1', 'gender' => $gender, 'is_driver' => $driver ? '1' : '0'];
    }

    private function render(array $result) {
        $pairingModeName = '家族を一緒に';
        $executionTime = 1.5;
        ob_start();
        include __DIR__ . '/../templates/pairing.php';
        return ob_get_clean();
    }

    public function testRendersCarsAndWalkers() {
        $html = $this->render([
            'cars' => [[$this->person('甲', true), $this->person('乙', false, 'F')]],
            'walk' => [$this->person('丙', false)],
            'score' => 0,
        ]);
        $this->assert(substr_count($html, 'class="car-card"') === 2 || substr_count($html, 'car-card') >= 2, '車と徒歩のカードが表示されていません');
        $this->assert(strpos($html, '🚶 徒歩') !== false, '徒歩の表示がありません');
        $this->assert(substr_count($html, 'name="decide"') === 1, '決定ボタンは下部の1つだけであるべきです');
    }

    public function testGroupLockButtonsAndRetryPost() {
        $a = $this->person('甲', true);
        $html = $this->render(['cars' => [[$a], [$a]], 'walk' => [], 'score' => 0, 'locked' => ['1']]);
        $this->assert(substr_count($html, 'group-lock') === 2, '各車の下部に固定ボタンを配置すること');
        $this->assert(strpos($html, 'name="retry"') !== false, '再生成を現在の配置とともにPOSTすること');
        $this->assert(strpos($html, '&quot;locked&quot;:[&quot;1&quot;]') !== false, '再生成後の固定状態を渡すこと');
    }

    public function testErrorHasNoDecideButton() {
        $html = $this->render(['error' => 'ドライバーがいません']);
        $this->assert(strpos($html, 'name="decide"') === false, 'エラー時に決定ボタンを出してはいけません');
        $this->assert(strpos($html, 'pairing-editor.js') === false, 'エラー時には編集機能を読み込まないこと');
    }

    public function testEditableMembersAndEmptyWalkTarget() {
        $html = $this->render(['cars' => [[$this->person('甲', true)]], 'walk' => [], 'score' => 0]);
        foreach (['id="pairing-editor"', 'data-member-id="甲"', 'draggable="true"', 'data-group="0"',
            'data-group="walk"', 'id="pairing-result"', 'pairing-editor.js', 'aria-pressed="false"',
            'id="add-pairing-group"', '空グループを追加', '手動調整では家族を分けられます'] as $markup) {
            $this->assert(strpos($html, $markup) !== false, "$markup がありません");
        }
    }

    public function testAlphabeticalCarLabels() {
        $cars = array_fill(0, 28, [$this->person('甲', true)]);
        $html = $this->render(['cars' => $cars, 'walk' => [], 'score' => 0]);
        foreach (['車A', '車B', '車Z', '車AA', '車AB'] as $label) {
            $this->assert(strpos($html, "<h3>🚗 $label</h3>") !== false, "$label の表示がありません");
            $this->assert(strpos($html, "aria-label=\"{$label}に移動\"") !== false, "$label の操作ラベルがありません");
        }
        $this->assert(strpos($html, '車 1') === false, '数字の車名が残っています');
    }

    public function testCompactMobileStyles() {
        $css = file_get_contents(__DIR__ . '/../public/style.css');
        $this->assert(preg_match('/\.bottom-actions \{[^}]*position: sticky/s', $css) === 1, '下部ボタンをsticky固定するスタイルがありません');
        $this->assert(strrpos($css, '@media (max-width: 640px)') > strpos($css, '.car-card {'), 'モバイル用の指定は基本定義より後ろに置くこと');
    }

    public function testGenderByColorAndDriverEmoji() {
        $html = $this->render([
            'cars' => [[$this->person('甲', true, 'M'), $this->person('乙', false, 'F')]],
            'walk' => [$this->person('丙', false, 'F')],
            'score' => 0,
        ]);
        $this->assert(strpos($html, '<strong class="man">甲') !== false, '男性の名前に .man が付いていません');
        $this->assert(strpos($html, '<strong class="woman">乙') !== false, '女性の名前に .woman が付いていません');
        $this->assert(strpos($html, '♂') === false && strpos($html, '♀') === false, '♂♀の表記は消すこと');
        $this->assert(strpos($html, '🔑') === false, '鍵の絵文字は使わないこと');
        $this->assert(substr_count($html, '🚗') === 2, 'ドライバーは🚗(見出しの車1つ+ドライバー1人)');
        $this->assert(substr_count($html, '👤') === 2, '乗客は👤(乙・丙)');
    }

    public function testScoreRoundedToOneDecimal() {
        $result = ['cars' => [[$this->person('甲', true)]], 'walk' => [], 'score' => 272.66666666667];
        $html = $this->render($result);
        $this->assert(strpos($html, '履歴スコア:</strong> 272.7 ') !== false, '履歴スコアが小数点1桁に丸められていません');
        $this->assert(strpos($html, '272.66') === false, '丸める前の値が残っています');
        $this->assert(strpos($html, 'スコア (履歴重複度)') === false, '旧ラベルが残っています');
        $result['score'] = 0;
        $this->assert(strpos($this->render($result), '履歴スコア:</strong> 0.0 ') !== false, '整数のスコアも小数点1桁で表示すること');
    }

    public function testFamilyInitialAfterNameInCarsAndWalk() {
        $a = $this->person('甲', true); $a['family_id'] = '田中家';
        $b = $this->person('丙', false); $b['family_id'] = '鈴木家';
        $html = $this->render(['cars' => [[$a]], 'walk' => [$b], 'score' => 0]);
        $this->assert(substr_count($html, 'class="family-initial"') === 2, '車と徒歩に家族バッジを表示すること');
        $this->assert(preg_match('/甲<\/strong>\s*<span class="family-initial"[^>]*>田<\/span>/u', $html) === 1, '名前の後ろに家族の先頭1文字を表示すること');
        $this->assert(strpos($html, '>鈴</span>') !== false, '徒歩にも日本語の先頭文字を表示すること');
        $this->assert(strpos($html, 'family-tag') === false && strpos($html, 'passenger-meta') === false, '以前の家族ID表示を残さないこと');
    }

    public function testNoHeaderMenu() {
        $html = $this->render(['cars' => [[$this->person('甲', true)]], 'walk' => [], 'score' => 0]);
        $head = substr($html, 0, strpos($html, 'class="alert alert-info"'));
        $this->assert(strpos($head, 'btn') === false, 'タイトル右側のメニューは表示しないこと');
        $this->assert(strpos($html, 'header-actions') === false, '上部ボタンのマークアップが残っています');
    }

    public function testDriversListedFirst() {
        $html = $this->render([
            'cars' => [[$this->person('乗客A', false), $this->person('運転手', true), $this->person('乗客B', false)]],
            'walk' => [],
            'score' => 0,
        ]);
        $d = strpos($html, '運転手'); $a = strpos($html, '乗客A'); $b = strpos($html, '乗客B');
        $this->assert($d < $a && $a < $b, 'ドライバーが先頭で、乗客は元の順序であるべきです');
    }

    public function testScoreLabelShort() {
        $html = $this->render(['cars' => [[$this->person('甲', true)]], 'walk' => [], 'score' => 1]);
        $this->assert(strpos($html, '（小さいと良い）') !== false, '注記は「（小さいと良い）」であるべきです');
        $this->assert(strpos($html, '小さいほど良い') === false, '旧文言が残っています');
    }

    public function testRetryButtonLabelAndPcSpacing() {
        $html = $this->render(['cars' => [[$this->person('甲', true)]], 'walk' => [], 'score' => 0]);
        $this->assert(strpos($html, '🔄 組合せをやり直す') !== false, '「組合せをやり直す」ボタンがありません');
        $this->assert(strpos($html, 'もう一回') === false, '旧ラベルが残っています');
        $css = file_get_contents(__DIR__ . '/../public/style.css');
        $base = substr($css, 0, strrpos($css, '@media (max-width: 640px)'));
        $this->assert(preg_match('/\.bottom-actions \{[^}]*gap: 1\.5rem/s', $base) === 1, 'PCではボタン間の隙間を広めにすること');
        $mobile = substr($css, strrpos($css, '@media (max-width: 640px)'));
        $this->assert(preg_match('/\.bottom-actions \{[^}]*gap: 0\.4rem/s', $mobile) === 1, 'スマホでは隙間を詰めること');
    }
}
