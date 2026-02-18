# CSVデータインポート手順

## 概要
約3万件の参照名簿データをCSVファイルからインポートするArtisanコマンドです。

## CSVファイルの構造（14列）

| 列番号 | カラム名 | 説明 | 例 |
|--------|----------|------|-----|
| 0 | graduation_term | 卒業回 | 高校51回期 |
| 1 | name | 氏名 | 相河 奈美 |
| 2 | gender | 性別 | 女 |
| 3 | status | 状態/会員区分 | 一般 |
| 4 | role_1 | 役職1 | 理事 |
| 5 | role_2 | 役職2 | 常任理事 |
| 6 | former_name | 旧姓 | 野原 |
| 7 | kana | フリガナ | ｱｲｶﾜ ﾅﾐ |
| 8 | notes | 備考/更新履歴 | 2018.4郵便物返却 |
| 9 | postal_code | 郵便番号 | 923-0931 |
| 10 | address_1 | 住所1 | 石川県小松市 |
| 11 | address_2 | 住所2 | 大文字町１３０番地 |
| 12 | address_3 | 住所3 | （マンション名など） |
| 13 | phone | 電話番号 | 0761-21-5112 |

## インポート手順

### 1. CSVファイルの配置
```bash
# Dockerコンテナ内にCSVファイルをコピー
docker cp rosters.csv matsu-app:/var/www/html/storage/app/rosters.csv
```

または、Windowsのエクスプローラーで `c:\Users\bybyb\myPrg\matsu.net\storage\app\` フォルダに `rosters.csv` を配置してください。

### 2. インポートコマンド実行（基本）
```bash
docker exec matsu-app php artisan import:rosters
```

### 3. オプション付きコマンド

#### テーブルをクリアしてからインポート
```bash
docker exec matsu-app php artisan import:rosters --truncate
```

#### チャンクサイズを変更（デフォルト: 1000件）
```bash
docker exec matsu-app php artisan import:rosters --chunk=500
```

#### 異なるファイルパスを指定
```bash
docker exec matsu-app php artisan import:rosters backup_rosters.csv
```

## 実行結果の例

```
📂 CSVファイル: /var/www/html/storage/app/rosters.csv
📊 ファイルサイズ: 3.25 MB
⚙️  チャンクサイズ: 1000件

🚀 インポートを開始します...
📋 ヘッダー: 卒業回, 氏名, 性別, 状態, 役職1...
✅ 1000 件インポート完了...
✅ 2000 件インポート完了...
...
✅ 30000 件インポート完了...

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
✨ インポート完了
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
┌──────────┬────────┐
│ 項目     │ 件数   │
├──────────┼────────┤
│ 処理行数 │ 30,000 │
│ 成功     │ 30,000 │
│ エラー   │ 0      │
│ 処理時間 │ 45.23秒 │
│ 処理速度 │ 663件/秒 │
└──────────┴────────┘
📊 テーブル総件数: 30,000
```

## データ確認

### 1. データベースで確認（phpMyAdmin）
http://localhost:8082 にアクセス
- サーバー: matsu-db
- ユーザー名: matsu_user
- パスワード: matsu_password
- データベース: matsu_net
- テーブル: reference_rosters

### 2. Artisanコマンドで確認（推奨）
```bash
# 最初の5件を表示（デフォルト）
docker exec matsu-app php artisan rosters:show

# 最初の10件を表示
docker exec matsu-app php artisan rosters:show --limit=10

# 特定の卒業回で絞り込み（例: 51回期）
docker exec matsu-app php artisan rosters:show --term=51

# 特定の氏名で絞り込み（例: 相河）
docker exec matsu-app php artisan rosters:show --name=相河
```

### 3. Laravelのtinkerで確認
```bash
docker exec -it matsu-app php artisan tinker
```

```php
// 総件数確認
\App\Models\ReferenceRoster::count();

// 最初の10件を表示
\App\Models\ReferenceRoster::limit(10)->get();

// 特定の卒業回で検索
\App\Models\ReferenceRoster::where('graduation_term', 'LIKE', '%51回期%')->count();

// 登録済みフラグで絞り込み
\App\Models\ReferenceRoster::where('is_registered', false)->count();
```

## 検索インデックス

以下のインデックスが自動的に作成されます（高速検索のため）：

- `idx_graduation_term`: 卒業回での検索
- `idx_name`: 氏名での検索
- `idx_kana`: フリガナでの検索
- `idx_term_name`: 卒業回 + 氏名の複合検索
- `idx_term_kana`: 卒業回 + フリガナの複合検索
- `idx_is_registered`: 登録済みフラグでの絞り込み

## 新規登録時の照合処理

ユーザーが新規登録する際、以下のロジックで参照名簿と照合されます：

1. **完全一致**: 卒業回 + 氏名が完全一致 → 自動承認
2. **部分一致**: 卒業回 + 氏名の部分一致 → 承認待ち
3. **カナ一致**: 卒業回 + フリガナの部分一致 → 承認待ち
4. **不一致**: どれにも該当しない → 承認待ち

## 卒業年度の変換

RegisterControllerの `convertYearToTerm()` メソッドで、卒業年度（数値）を卒業回（文字列）に変換しています。

現在の設定:
```php
// 例: 2018年卒業 = 高校51回期
$term = $year - 1967; // 2018 - 1967 = 51
return "高校{$term}回期";
```

**※実際の卒業回の命名規則に合わせて調整してください。**

## トラブルシューティング

### エラー: "File does not exist"
→ CSVファイルが配置されているか確認してください
```bash
docker exec matsu-app ls -lh /var/www/html/storage/app/rosters.csv
```

### エラー: "列数が不足しています"
→ CSV行が14列未満の場合は警告が表示されます。CSVファイルの形式を確認してください

### メモリ不足エラー
→ チャンクサイズを減らしてください
```bash
docker exec matsu-app php artisan import:rosters --chunk=500
```

### 文字化け
→ CSVファイルの文字コードがShift_JISであることを確認してください
（コマンド内で自動的にUTF-8に変換されます）

## 注意事項

- テーブルクリア（`--truncate`）は既存データを削除するため、本番環境では慎重に使用してください
- 3万件のデータをインポートする場合、約40〜60秒程度かかります
- インポート中は他の処理を実行しないでください
- ログは `storage/logs/laravel.log` に記録されます

## 関連ファイル

- マイグレーション: `database/migrations/2024_01_01_000002_create_reference_rosters_table.php`
- コマンド: `app/Console/Commands/ImportRosters.php`
- モデル: `app/Models/ReferenceRoster.php`
- コントローラー: `app/Http/Controllers/RegisterController.php`
