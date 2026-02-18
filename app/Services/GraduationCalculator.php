<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * 卒業年度・回期の計算サービス
 * 
 * 【計算基準】
 * - 高校51回期 = 1999年3月卒業（1998年度）
 * - 高校1回期 = 1949年3月卒業（1948年度）
 * - 回期番号 = 卒業年度 - 1947
 * - 卒業年度 = 回期番号 + 1947
 */
class GraduationCalculator
{
    /**
     * 基準年度（1回期の卒業年度）
     */
    const BASE_YEAR = 1947;

    /**
     * 高校卒業時の標準年齢
     */
    const GRADUATION_AGE = 18;

    /**
     * 生年月日から現役卒業年度を計算
     * 
     * @param string|Carbon $birthDate 生年月日
     * @return int 卒業年度（例：1998）
     */
    public static function calculateGraduationYear($birthDate): int
    {
        $birth = $birthDate instanceof Carbon ? $birthDate : Carbon::parse($birthDate);
        
        // 学年の基準日：4月1日
        // 4月2日〜翌年4月1日生まれが同じ学年
        $birthYear = $birth->year;
        $birthMonth = $birth->month;
        $birthDay = $birth->day;
        
        // 18歳になる年度を計算（高校現役卒業）
        // 4月2日〜翌年4月1日生まれが同じ学年
        if ($birthMonth > 4 || ($birthMonth === 4 && $birthDay >= 2)) {
            // 4月2日以降生まれ → 生まれた年 + 18
            return $birthYear + self::GRADUATION_AGE;
        } else {
            // 1月1日〜4月1日生まれ（早生まれ） → 生まれた年 + 17
            return $birthYear + self::GRADUATION_AGE - 1;
        }
    }

    /**
     * 卒業年度から回期番号を計算
     * 
     * @param int $graduationYear 卒業年度（例：1998）
     * @return int 回期番号（例：51）
     */
    public static function yearToTerm(int $graduationYear): int
    {
        return $graduationYear - self::BASE_YEAR;
    }

    /**
     * 回期番号から卒業年度を計算
     * 
     * @param int $termNumber 回期番号（例：51）
     * @return int 卒業年度（例：1998）
     */
    public static function termToYear(int $termNumber): int
    {
        return $termNumber + self::BASE_YEAR;
    }

    /**
     * 生年月日から回期番号を計算
     * 
     * @param string|Carbon $birthDate 生年月日
     * @return int 回期番号（例：51）
     */
    public static function calculateTerm($birthDate): int
    {
        $graduationYear = self::calculateGraduationYear($birthDate);
        return self::yearToTerm($graduationYear);
    }

    /**
     * 生年月日から卒業年月を取得（YYYY年MM月形式）
     * 
     * @param string|Carbon $birthDate 生年月日
     * @return string 卒業年月（例：1999年3月）
     */
    public static function getGraduationMonth($birthDate): string
    {
        $graduationYear = self::calculateGraduationYear($birthDate);
        $graduationMonth = $graduationYear + 1; // 卒業は翌年の3月
        return "{$graduationMonth}年3月";
    }

    /**
     * 回期番号から卒業年月を取得
     * 
     * @param int $termNumber 回期番号（例：51）
     * @return string 卒業年月（例：1999年3月）
     */
    public static function termToGraduationMonth(int $termNumber): string
    {
        $graduationYear = self::termToYear($termNumber);
        $graduationMonth = $graduationYear + 1; // 卒業は翌年の3月
        return "{$graduationMonth}年3月";
    }

    /**
     * 生年月日から卒業年度の選択肢を生成（前後N年）
     * 
     * @param string|Carbon $birthDate 生年月日
     * @param int $range 前後の年数（デフォルト：1）
     * @return array 選択肢の配列 [['value' => '高校51回期', 'label' => '1999年3月卒（51回期）', 'is_default' => true], ...]
     */
    public static function getGraduationOptions($birthDate, int $range = 1): array
    {
        $baseYear = self::calculateGraduationYear($birthDate);
        $options = [];

        for ($i = -$range; $i <= $range; $i++) {
            $year = $baseYear + $i;
            $termNumber = self::yearToTerm($year);
            $graduationMonth = $year + 1; // 卒業は翌年の3月

            $label = "{$graduationMonth}年3月卒（{$termNumber}回期）";
            
            if ($i === 0) {
                $label .= '【現役想定】';
            } elseif ($i < 0) {
                $label .= '【早期卒業】';
            } else {
                $label .= '【留年等】';
            }

            $options[] = [
                'value' => "高校{$termNumber}回期",
                'label' => $label,
                'year' => $year,
                'term' => $termNumber,
                'graduation_month' => "{$graduationMonth}年3月",
                'is_default' => ($i === 0),
            ];
        }

        return $options;
    }

    /**
     * 回期文字列から回期番号を抽出
     * 
     * @param string $termString 回期文字列（例：高校51回期）
     * @return int|null 回期番号（例：51）
     */
    public static function extractTermNumber(string $termString): ?int
    {
        if (preg_match('/(\d+)回期/', $termString, $matches)) {
            return (int) $matches[1];
        }
        return null;
    }

    /**
     * 回期文字列から卒業年度を取得
     * 
     * @param string $termString 回期文字列（例：高校51回期）
     * @return int|null 卒業年度（例：1998）
     */
    public static function extractGraduationYear(string $termString): ?int
    {
        $termNumber = self::extractTermNumber($termString);
        return $termNumber ? self::termToYear($termNumber) : null;
    }
}
