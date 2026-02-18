<?php

/**
 * 卒業年度・回期計算のテストケース
 * 
 * 実行方法：
 * docker compose exec app php /var/www/html/tests/GraduationCalculatorTest.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\GraduationCalculator;

echo "========================================\n";
echo "卒業年度・回期計算テスト\n";
echo "========================================\n\n";

// テストケース
$testCases = [
    // [生年月日, 期待される卒業年度, 期待される回期, 説明]
    ['1980-05-15', 1998, 51, '1980年5月生まれ（通常）'],
    ['1981-03-15', 1998, 51, '1981年3月生まれ（早生まれ）'],
    ['1981-04-01', 1998, 51, '1981年4月1日生まれ（早生まれの境界）'],
    ['1981-04-02', 1999, 52, '1981年4月2日生まれ（次の学年）'],
    ['1930-06-01', 1948, 1, '1回期の想定（1930年生まれ）'],
    ['2000-10-20', 2018, 71, '2000年生まれ'],
];

echo "【テストケース】\n";
foreach ($testCases as $index => $case) {
    list($birthDate, $expectedYear, $expectedTerm, $description) = $case;
    
    $calculatedYear = GraduationCalculator::calculateGraduationYear($birthDate);
    $calculatedTerm = GraduationCalculator::calculateTerm($birthDate);
    $graduationMonth = GraduationCalculator::getGraduationMonth($birthDate);
    
    $yearMatch = $calculatedYear === $expectedYear ? '✓' : '✗';
    $termMatch = $calculatedTerm === $expectedTerm ? '✓' : '✗';
    
    echo sprintf(
        "%d. %s\n   生年月日: %s\n   卒業年度: %d %s (期待: %d)\n   回期: %d回期 %s (期待: %d回期)\n   卒業年月: %s\n\n",
        $index + 1,
        $description,
        $birthDate,
        $calculatedYear,
        $yearMatch,
        $expectedYear,
        $calculatedTerm,
        $termMatch,
        $expectedTerm,
        $graduationMonth
    );
}

echo "\n【変換テスト】\n";

// 回期 → 卒業年度
$termToYear = [
    1 => 1948,
    51 => 1998,
    71 => 2018,
];

foreach ($termToYear as $term => $expectedYear) {
    $year = GraduationCalculator::termToYear($term);
    $match = $year === $expectedYear ? '✓' : '✗';
    echo sprintf(
        "%d回期 → %d年度 %s (期待: %d)\n",
        $term,
        $year,
        $match,
        $expectedYear
    );
}

echo "\n";

// 卒業年度 → 回期
$yearToTerm = [
    1948 => 1,
    1998 => 51,
    2018 => 71,
];

foreach ($yearToTerm as $year => $expectedTerm) {
    $term = GraduationCalculator::yearToTerm($year);
    $match = $term === $expectedTerm ? '✓' : '✗';
    echo sprintf(
        "%d年度 → %d回期 %s (期待: %d回期)\n",
        $year,
        $term,
        $match,
        $expectedTerm
    );
}

echo "\n【文字列抽出テスト】\n";

$termStrings = [
    '高校51回期' => [51, 1998],
    '高校1回期' => [1, 1948],
    '高校71回期' => [71, 2018],
];

foreach ($termStrings as $string => $expected) {
    list($expectedTerm, $expectedYear) = $expected;
    
    $term = GraduationCalculator::extractTermNumber($string);
    $year = GraduationCalculator::extractGraduationYear($string);
    
    $termMatch = $term === $expectedTerm ? '✓' : '✗';
    $yearMatch = $year === $expectedYear ? '✓' : '✗';
    
    echo sprintf(
        "「%s」→ %d回期 %s, %d年度 %s\n",
        $string,
        $term,
        $termMatch,
        $year,
        $yearMatch
    );
}

echo "\n【選択肢生成テスト】\n";
$options = GraduationCalculator::getGraduationOptions('1980-05-15', 1);
foreach ($options as $option) {
    $default = $option['is_default'] ? ' [デフォルト]' : '';
    echo sprintf(
        "value=\"%s\" label=\"%s\"%s\n",
        $option['value'],
        $option['label'],
        $default
    );
}

echo "\n========================================\n";
echo "テスト完了\n";
echo "========================================\n";
