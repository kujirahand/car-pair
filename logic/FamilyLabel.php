<?php

class FamilyLabel {
    public static function hue($family): int {
        // 家族ID全体から決め、移動・再読み込み・履歴表示でも同じ色を使う。
        $hash = hash('sha256', trim((string)$family));
        return (int)hexdec(substr($hash, 0, 4)) % 360;
    }

    public static function initial($family): string {
        $family = trim((string)$family);
        // mbstringがないPHPでも、日本語や絵文字を1文字として切り出す。
        return preg_match('/^./us', $family, $match) === 1 ? $match[0] : '';
    }
}
