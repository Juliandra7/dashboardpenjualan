<?php

namespace App\Support;

class Currency
{
    public static function format(float|int $value): string
    {
        $value = (float) $value;
        $formatted = number_format(abs($value), 2, '.', ',');

        return $value < 0 ? '-$'.$formatted : '$'.$formatted;
    }

    public static function short(float|int $value): string
    {
        $value = (float) $value;
        $absolute = abs($value);
        $suffix = '';
        $divisor = 1;

        if ($absolute >= 1_000_000_000) {
            $divisor = 1_000_000_000;
            $suffix = 'B';
        } elseif ($absolute >= 1_000_000) {
            $divisor = 1_000_000;
            $suffix = 'M';
        } elseif ($absolute >= 1_000) {
            $divisor = 1_000;
            $suffix = 'K';
        }

        $scaled = $value / $divisor;

        return '$'.number_format($scaled, $suffix === '' ? 2 : 1, '.', '').$suffix;
    }
}
