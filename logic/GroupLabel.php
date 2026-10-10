<?php

class GroupLabel {
    // A〜Zの次はAA、AB…とする。
    public static function car(int $index): string {
        $label = '';
        do {
            $label = chr(65 + $index % 26) . $label;
            $index = intdiv($index, 26) - 1;
        } while ($index >= 0);
        return '車' . $label;
    }
}
