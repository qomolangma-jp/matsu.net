# ローカル環境での新規登録とオートログイン

## 📝 概要

ローカル環境（`matsu.localhost`）では、LINE LIFF SDKが利用できないため、以下の機能を実装しました：

1. **仮のLINE ID入力フォーム** - 手動でLINE IDを入力
2. **LIFF初期化スキップ** - ローカル環境ではLIFF SDKを使用しない
3. **オートログイン機能** - 登録完了後、自動的にログイン

## 🚀 使い方

### 1. 新規登録画面にアクセス
```
http://matsu.localhost/register
```

### 2. フォームに入力

#### LINE ID（テスト用）
- ローカル環境では入力フィールドが表示されます
- デフォルト値が自動生成されます（例: `local_test_65d3f2a1b4c5e`）
- 好きな値に変更可能（重複しない任意の文字列）

#### その他の項目
- 姓・名
- 姓（カナ）・名（カナ）
- 生年月日
- 卒業年度（生年月日から自動計算）
- メールアドレス
- 郵便番号・住所

### 3. 登録実行

「登録する」ボタンをクリックすると：

#### ケース1: 参照名簿と完全一致
1. 登録が完了
2. 自動的に承認（`approval_status = 'approved'`）
3. **オートログインが実行される**
4. 登録完了画面が表示
5. 管理者の場合は管理画面へのリンクが表示

#### ケース2: 部分一致または不一致
1. 登録申請を受付
2. 承認待ち状態（`approval_status = 'pending'`）
3. **オートログインが実行される**（承認待ちでもログイン可能）
4. 管理者にメール通知
5. 登録完了画面が表示

## 🔐 オートログイン後の動作

### 一般ユーザー
- トップページへのリンクが表示
- ログイン状態が維持される

### 管理者（master_admin / year_admin）
- **管理画面へのリンクが表示**
- 直接管理機能にアクセス可能

### ログイン状態の確認
登録完了画面に以下が表示されます：
```
✅ 自動ログイン完了
ようこそ、山田 太郎 さん
参照名簿と完全一致したため、自動的に承認されました。
```

または（承認待ちの場合）：
```
📌 ログイン完了
ようこそ、山田 太郎 さん
承認待ちの状態ですが、一部機能はご利用いただけます。
```

## 🧪 テストシナリオ

### シナリオ1: 参照名簿と完全一致するユーザーを登録

```
LINE ID: local_test_完全一致
姓: 山田
名: 太郎
姓（カナ）: ヤマダ
名（カナ）: タロウ
生年月日: 2000-04-15
卒業年度: 2018年
メールアドレス: yamada@example.com
```

**期待される動作:**
1. 自動承認
2. オートログイン
3. 登録完了画面でログイン状態を確認
4. トップページまたは管理画面にアクセス可能

### シナリオ2: 参照名簿と部分一致するユーザーを登録

```
LINE ID: local_test_部分一致
姓: 佐藤
名: 花子
姓（カナ）: サトウ
名（カナ）: ハナコ
生年月日: 2000-06-20
卒業年度: 2018年
メールアドレス: satoh@example.com
```

**期待される動作:**
1. 承認待ち
2. オートログイン
3. 管理者にメール通知
4. 登録完了画面でログイン状態を確認

### シナリオ3: 管理者として登録（テストデータに既存）

参照名簿に管理者フラグがある場合：

```
LINE ID: local_test_admin
姓: 管理
名: 太郎
生年月日: 1995-04-01
卒業年度: 2013年
※ 参照名簿で is_admin = true のレコード
```

**期待される動作:**
1. 自動承認
2. roleが 'year_admin' または 'master_admin' に設定
3. オートログイン
4. 登録完了画面に「管理画面へ」ボタンが表示

## 📌 重要な仕様

### LINE IDの重複チェック
- 同じLINE IDは登録できません
- バリデーションエラーが表示されます
- ローカルでテストする場合は、毎回異なるLINE IDを使用してください

### セッション管理
- 登録後のセッションは通常のLaravelセッション
- `Auth::login($user)` で認証状態を作成
- ログアウトは管理画面のサイドバーから可能

### 環境判定
コード内で以下の方法で環境を判定しています：

**Blade（PHP）:**
```php
@if(config('app.env') === 'local')
    <!-- ローカル環境用の処理 -->
@endif
```

**JavaScript:**
```javascript
const isLocal = window.location.hostname === 'matsu.localhost' || 
                window.location.hostname === 'localhost' || 
                window.location.hostname === '127.0.0.1';
```

## 🔍 デバッグ方法

### ログインセッションの確認
ブラウザの開発者ツールで Cookies を確認：
- `net_session` クッキーが存在すればログイン中

### データベースで確認
```bash
docker exec matsu-app php artisan tinker
```

```php
// 最新のユーザーを確認
User::latest()->first();

// ログイン状態を確認（Webリクエスト中のみ有効）
Auth::check();
Auth::user();
```

### ログファイルの確認
```bash
# Laravel ログ
docker exec matsu-app tail -f storage/logs/laravel.log

# Apache アクセスログ
docker logs matsu-app
```

## 🚨 トラブルシューティング

### LINE IDの重複エラー
```
The line id has already been taken.
```

**解決方法：** フォームのLINE ID欄で異なる値を入力

### オートログインが動作しない
1. セッションドライバーの確認: `.env` の `SESSION_DRIVER=file`
2. storage/framework/sessions ディレクトリの権限確認
3. ブラウザのCookieを削除して再試行

### 登録完了画面でログイン状態が表示されない
- `Auth::login($user)` が正しく実行されているか確認
- リダイレクト前にセッションがコミットされているか確認

## 📚 関連ファイル

- [app/Http/Controllers/RegisterController.php](app/Http/Controllers/RegisterController.php) - オートログイン処理
- [resources/views/register.blade.php](resources/views/register.blade.php) - LINE ID入力フォーム
- [public/js/register.js](public/js/register.js) - LIFF初期化スキップ
- [resources/views/register-complete.blade.php](resources/views/register-complete.blade.php) - ログイン状態表示

## 🔗 次のステップ

登録・ログイン後は以下の機能を利用できます：

- 管理者の場合: [管理画面へのアクセス](ADMIN_LOGIN.md)
- 一般ユーザー: トップページでニュース・イベント閲覧
