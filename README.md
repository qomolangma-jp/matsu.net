# 松.net - 同窓生向けWebシステム

LINE LIFFを活用した同窓生向けWebシステムです。

## システム構成

- **フロントエンド**: Laravel Blade + Bootstrap 5
- **バックエンド**: Laravel 11.x (PHP 8.2)
- **データベース**: MySQL 8.0
- **リバースプロキシ**: Caddy 2
- **実行環境**: Docker Compose

## 必要な環境

- Docker Desktop
- Git

## セットアップ手順

### 1. リポジトリのクローン（または既存ディレクトリ）

```bash
cd c:\Users\bybyb\myPrg\matsu.net
```

### 2. hostsファイルの編集

Windows の `C:\Windows\System32\drivers\etc\hosts` ファイルに以下を追加：

```
127.0.0.1 matsu.localhost
```

※ 管理者権限でメモ帳を開いて編集してください。

### 3. Laravelプロジェクトのセットアップ

```bash
# .envファイルの作成
cp .env.example .env

# Dockerコンテナの起動
docker-compose up -d

# Composerの依存関係インストール
docker exec -it pg-1geki-app composer install

# アプリケーションキーの生成
docker exec -it pg-1geki-app php artisan key:generate

# データベースマイグレーション実行
docker exec -it pg-1geki-app php artisan migrate

# ストレージリンクの作成
docker exec -it pg-1geki-app php artisan storage:link

# 権限設定
docker exec -it pg-1geki-app chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
```

### 4. .envファイルの設定

`c:\Users\bybyb\myPrg\matsu.net\.env` ファイルを開き、以下の項目を設定：

```env
APP_NAME="松.net"
APP_URL=http://matsu.localhost

# LINE LIFF設定（LINE Developersで取得）
LIFF_ID=your-liff-id-here
LINE_CHANNEL_ID=your-channel-id-here
LINE_CHANNEL_SECRET=your-channel-secret-here
```

### 5. アクセス

ブラウザで以下のURLにアクセス：

- **メインアプリ**: http://matsu.localhost
- **phpMyAdmin**: http://localhost:8080

## プロジェクト構造

```
matsu.net/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       └── RegisterController.php    # 新規登録コントローラー
│   └── Models/
│       └── User.php                       # ユーザーモデル
├── database/
│   └── migrations/                        # マイグレーションファイル
│       ├── 2024_01_01_000001_create_users_table.php
│       ├── 2024_01_01_000002_create_reference_rosters_table.php
│       ├── 2024_01_01_000003_create_events_table.php
│       ├── 2024_01_01_000004_create_news_table.php
│       └── 2024_01_01_000005_create_attendances_table.php
├── public/
│   └── js/
│       └── register.js                    # 新規登録フォームJS
├── resources/
│   └── views/
│       ├── layouts/
│       │   └── app.blade.php              # 共通レイアウト
│       ├── register.blade.php             # 新規登録画面
│       └── register-complete.blade.php    # 登録完了画面
├── routes/
│   └── web.php                            # ルーティング定義
├── docker-compose.yml                     # Docker Compose設定
├── Dockerfile                             # Laravel用Dockerfile
└── Caddyfile                              # リバースプロキシ設定
```

## 実装済み機能

### 新規登録画面

- ✅ LINE LIFF認証連携
- ✅ 氏名（姓・名）入力
- ✅ フリガナ入力
- ✅ 生年月日入力
- ✅ **卒業年度自動生成** - 生年月日から前後3年分の候補を動的生成
- ✅ メールアドレス入力
- ✅ 郵便番号入力
- ✅ **住所自動入力** - 郵便番号から住所を自動取得（zipcloud API使用）
- ✅ Bootstrap 5によるレスポンシブデザイン
- ✅ バリデーション機能

## データベーステーブル

| テーブル名 | 説明 |
|-----------|------|
| users | ユーザー情報（LINE ID、氏名、卒業年度、権限など） |
| reference_rosters | 参照用既存名簿（CSV一括インポート対応、約3万件） |
| events | イベント情報（同窓会、懇親会など） |
| news | お知らせ（TOP掲載、LINE通知機能付き） |
| attendances | 出欠管理（イベントとユーザーの関連） |

## 権限種別

1. **一般ユーザー** (`general`) - 基本的な閲覧・出欠登録
2. **学年管理者** (`year_admin`) - 卒業年度ごとの管理権限
3. **マスター管理者** (`master_admin`) - 全体管理権限

## JavaScriptの主要機能

### 1. 生年月日→卒業年度の自動計算

```javascript
// 生年月日から18歳時の卒業年度を基準に、前後3年分（計7年分）の選択肢を生成
// 早生まれ（1-3月生まれ）も考慮
```

### 2. 郵便番号→住所の自動入力

```javascript
// zipcloud APIを使用して、郵便番号から都道府県・市区町村を自動取得
// https://zipcloud.ibsnet.co.jp/api/search
```

## LINE LIFF設定

LINE Developersコンソールで以下を設定：

1. LIFFアプリを作成
2. エンドポイントURLに `http://matsu.localhost/register` を設定
3. LIFF IDを `.env` に設定

## 開発コマンド

```bash
# ログ確認
docker-compose logs -f app

# コンテナに入る
docker exec -it pg-1geki-app bash

# マイグレーションのロールバック
docker exec -it pg-1geki-app php artisan migrate:rollback

# キャッシュクリア
docker exec -it pg-1geki-app php artisan cache:clear
docker exec -it pg-1geki-app php artisan config:clear
docker exec -it pg-1geki-app php artisan view:clear

# コンテナの停止
docker-compose down

# コンテナとボリュームの削除（データも削除）
docker-compose down -v
```

## トラブルシューティング

### ポート競合エラー

他のアプリケーションが80番ポートを使用している場合：

```bash
# Windowsでポート使用状況を確認
netstat -ano | findstr :80

# 該当プロセスを停止するか、docker-compose.ymlのポート番号を変更
```

### 権限エラー

```bash
# ストレージとキャッシュの権限を修正
docker exec -it pg-1geki-app chown -R www-data:www-data /var/www/html/storage
docker exec -it pg-1geki-app chmod -R 775 /var/www/html/storage
```

### データベース接続エラー

```bash
# MySQLコンテナの状態確認
docker-compose ps

# データベース再起動
docker-compose restart db
```

## ライセンス

MIT License

## 作成者

松.netプロジェクトチーム

---

**🌲 松.net で同窓生とつながろう！**
