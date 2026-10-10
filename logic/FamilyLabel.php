<?php

class FamilyLabel {
    public static function initial($family): string {
        $family = trim((string)$family);
        // mbstringがないPHPでも、日本語や絵文字を1文字として切り出す。
        return preg_match('/^./us', $family, $match) === 1 ? $match[0] : '';
    }
}
