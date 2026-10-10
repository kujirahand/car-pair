<?php
require_once __DIR__ . '/../logic/FamilyLabel.php';

class FamilyLabelTest {
    private function assert($condition, $message) {
        if (!$condition) throw new Exception($message);
    }
    private function render($family) {
        $p = ['family_id' => $family];
        ob_start();
        include __DIR__ . '/../templates/family_badge.php';
        return ob_get_clean();
    }
    public function testUnicodeInitialAndEmptyFamily() {
        foreach (['田中家' => '田', '鈴木家' => '鈴', 'Smith' => 'S', '🚗家' => '🚗', ' 山田家 ' => '山', '' => '', '  ' => ''] as $family => $initial) {
            $this->assert(FamilyLabel::initial($family) === $initial, "先頭文字が正しくありません: $family");
        }
        $this->assert(FamilyLabel::initial(null) === '', '家族未設定は空文字にすること');
        $this->assert(strpos($this->render(''), 'family-initial') === false, '家族IDが空なら枠を表示しないこと');
    }
    public function testRoundedBadgeWithEscapedFullFamilyLabel() {
        $html = $this->render('田中家');
        $this->assert(strpos($html, '>田</span>') !== false, '表示は先頭1文字であること');
        $this->assert(strpos($html, 'title="田中家"') !== false && strpos($html, 'aria-label="家族: 田中家"') !== false, '家族ID全体も確認できること');
        $html = $this->render('<script>"家');
        $this->assert(strpos($html, '<script>') === false && strpos($html, '&lt;script&gt;&quot;家') !== false, '家族IDをエスケープすること');
        $this->assert(strpos($html, '>&lt;</span>') !== false, '先頭文字もエスケープすること');
        $css = file_get_contents(__DIR__ . '/../public/style.css');
        $this->assert(preg_match('/\.family-initial \{[^}]*border: 1px[^}]*border-radius: 5px/s', $css) === 1, '角丸の枠で表示すること');
    }
}
