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

    public function testStableColorBasedOnFullFamilyId() {
        $hue = FamilyLabel::hue('田中家');
        $this->assert(FamilyLabel::hue('田中家') === $hue && FamilyLabel::hue(' 田中家 ') === $hue, '同じ家族は常に同じ色で表示すること');
        $this->assert(FamilyLabel::hue('田辺家') !== $hue, '先頭文字が同じでも家族ID全体で色を決めること');
        $this->assert(FamilyLabel::hue('鈴木家') !== $hue, '異なる家族を色分けすること');
        foreach (['田中家', '鈴木家', '🚗家', '<script>"家', '', null] as $family) {
            $color = FamilyLabel::hue($family);
            $this->assert(is_int($color) && $color >= 0 && $color < 360, '色は安全な整数の色相にすること');
        }
        $html = $this->render('田中家');
        $this->assert(strpos($html, "style=\"--family-hue: $hue\"") !== false, '家族の色をspanに設定すること');
        $this->assert($this->render('田中家') === $html, '再描画しても色が変わらないこと');
        $css = file_get_contents(__DIR__ . '/../public/style.css');
        $this->assert(preg_match('/\.family-initial \{[^}]*border: 1px solid hsl\(var\(--family-hue[^}]*color: hsl\(var\(--family-hue[^}]*background: hsl\(var\(--family-hue/s', $css) === 1, '枠・文字・背景を同じ家族の色相で色分けすること');
    }
}
