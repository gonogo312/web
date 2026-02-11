<?php

namespace Jawira\PlantUml;

function encodep(string $text): string {
    $data = gzdeflate($text, 9);
    if ($data === false) {
        return '';
    }
    return encode64($data);
}

function encode64(string $data): string {
    $r = '';
    $len = strlen($data);
    for ($i = 0; $i < $len; $i += 3) {
        $b1 = ord($data[$i]);
        $b2 = ($i + 1 < $len) ? ord($data[$i + 1]) : 0;
        $b3 = ($i + 2 < $len) ? ord($data[$i + 2]) : 0;

        $c1 = $b1 >> 2;
        $c2 = (($b1 & 0x3) << 4) | ($b2 >> 4);
        $c3 = (($b2 & 0xF) << 2) | ($b3 >> 6);
        $c4 = $b3 & 0x3F;

        $r .= encode6bit($c1 & 0x3F);
        $r .= encode6bit($c2 & 0x3F);
        $r .= ($i + 1 < $len) ? encode6bit($c3 & 0x3F) : '';
        $r .= ($i + 2 < $len) ? encode6bit($c4 & 0x3F) : '';
    }
    return $r;
}

function encode6bit(int $b): string {
    if ($b < 10) return chr(48 + $b);
    $b -= 10;
    if ($b < 26) return chr(65 + $b);
    $b -= 26;
    if ($b < 26) return chr(97 + $b);
    $b -= 26;
    if ($b === 0) return '-';
    if ($b === 1) return '_';
    return '?';
}

