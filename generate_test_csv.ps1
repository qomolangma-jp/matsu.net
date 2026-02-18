# 大量データテスト用スクリプト
# 1000件のサンプルCSVを生成してパフォーマンステスト

$output = "storage/app/test_1000.csv"
$header = "卒業回,氏名,性別,状態,役職1,役職2,旧姓,フリガナ,備考/更新履歴,〒,住所1,住所2,住所3,電話番号"

$terms = @("高校50回期", "高校51回期", "高校52回期", "高校53回期", "高校54回期")
$lastNames = @("佐藤", "鈴木", "高橋", "田中", "伊藤", "渡辺", "山本", "中村", "小林", "加藤")
$firstNames = @("太郎", "花子", "一郎", "次郎", "三郎", "美咲", "健太", "翔太", "陽子", "涼子")
$genders = @("男", "女")
$statuses = @("一般", "不明")

$lines = @($header)

for ($i = 1; $i -le 1000; $i++) {
    $term = $terms | Get-Random
    $lastName = $lastNames | Get-Random
    $firstName = $firstNames | Get-Random
    $name = "$lastName $firstName"
    $gender = $genders | Get-Random
    $status = $statuses | Get-Random
    $kana = "ﾃｽﾄ $i"
    $postal = "920-" + (Get-Random -Minimum 1000 -Maximum 9999).ToString().PadLeft(4, '0')
    $address1 = "石川県金沢市"
    $address2 = "テスト町$i番地"
    $phone = "076-" + (Get-Random -Minimum 100 -Maximum 999) + "-" + (Get-Random -Minimum 1000 -Maximum 9999)
    
    $line = "$term,$name,$gender,$status,,,,{$kana},,$postal,$address1,$address2,,$phone"
    $lines += $line
}

$lines | Out-File -FilePath $output -Encoding UTF8

Write-Host "✅ 1000件のテストCSVを生成しました: $output" -ForegroundColor Green
Write-Host "📊 ファイルサイズ: $((Get-Item $output).Length / 1KB) KB" -ForegroundColor Cyan
