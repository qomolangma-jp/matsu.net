<?php

namespace App\Support;

class CsvSanitizer
{
    /**
     * スプレッドシートの数式として解釈される先頭文字を無効化する
     */
    public static function cell(mixed $value): mixed
    {
        if (is_string($value) && $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $value;
        }

        return $value;
    }

    public static function row(array $row): array
    {
        return array_map([self::class, 'cell'], $row);
    }
}