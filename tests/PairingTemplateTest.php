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
        $this->assert(substr_count($html, 'name="decide"') === 2, '決定ボタンは上下に1つずつ必要です');
    }

    public function testErrorHasNoDecideButton() {
        $html = $this->render(['error' => 'ドライバーがいません']);
        $this->assert(strpos($html, 'name="decide"') === false, 'エラー時に決定ボタンを出してはいけません');
    }

    public function testCompactMobileStyles() {
        $css = file_get_contents(__DIR__ . '/../public/style.css');
        $this->assert(strpos($css, '.header-actions') !== false, 'スマホで上部ボタンを隠すスタイルがありません');
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
}
