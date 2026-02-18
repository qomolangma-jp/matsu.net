# 新規登録の照合・承認ロジック

## 概要

新規登録時に、参照名簿（reference_rosters）との照合を行い、自動承認または承認待ちの状態に分けて処理します。

## 照合ロジック

### 1. 完全一致（自動承認）

以下のすべてが一致する場合、自動的に承認されます：
- 姓（last_name）
- 名（first_name）
- 生年月日（birth_date）
- 卒業年度（graduation_year）

**処理:**
- `approval_status` = `approved`
- `approved_at` = 現在時刻
- `approval_note` = "参照名簿と完全一致のため自動承認"
- 参照名簿の`is_registered`フラグを`true`に更新

### 2. 部分一致（承認待ち）

以下のいずれかが一致し、完全一致しない場合：

**パターンA: 姓名 + 卒業年度**
- 姓（last_name）
- 名（first_name）
- 卒業年度（graduation_year）

**パターンB: カナ姓名 + 卒業年度**
- 姓カナ（last_name_kana）
- 名カナ（first_name_kana）
- 卒業年度（graduation_year）

**処理:**
- `approval_status` = `pending`
- 承認依頼メールを送信

### 3. 不一致（承認待ち）

参照名簿に該当データが見つからない場合。

**処理:**
- `approval_status` = `pending`
- 承認依頼メールを送信

## 承認依頼メール送信先の決定ロジック

### 優先順位1: 学年管理者

該当卒業年度の学年管理者を検索：
```php
User::where('graduation_year', $user->graduation_year)
    ->where('role', 'year_admin')
    ->where('approval_status', 'approved')
    ->whereNotNull('email')
    ->first();
```

### 優先順位2: マスター管理者

学年管理者が見つからない場合、マスター管理者を検索：
```php
User::where('role', 'master_admin')
    ->where('approval_status', 'approved')
    ->whereNotNull('email')
    ->first();
```

### 承認者が見つからない場合

ログに警告を記録し、メールは送信しません：
```
LOG: No approver found for user registration
```

## データベース構造

### usersテーブルの追加カラム

| カラム名 | 型 | 説明 |
|---------|-----|------|
| approval_status | enum('pending', 'approved', 'rejected') | 承認ステータス |
| approved_at | timestamp | 承認日時 |
| approved_by | bigint (foreign key) | 承認者のユーザーID |
| approval_note | text | 承認メモ |

### マイグレーション実行

```bash
docker exec matsu-app php artisan migrate
```

## メール設定

### 開発環境（ログ出力）

`.env`ファイル：
```env
MAIL_MAILER=log
MAIL_FROM_ADDRESS="noreply@matsu.localhost"
MAIL_FROM_NAME="松.net"
```

メールは `storage/logs/laravel.log` に出力されます。

### 本番環境（SMTP）

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@matsu.net"
MAIL_FROM_NAME="松.net"
```

## テストデータ作成例

### 参照名簿データの登録

```sql
INSERT INTO reference_rosters (last_name, first_name, last_name_kana, first_name_kana, birth_date, graduation_year, email, created_at, updated_at)
VALUES
('山田', '太郎', 'ヤマダ', 'タロウ', '2000-04-15', 2018, 'yamada@example.com', NOW(), NOW()),
('佐藤', '花子', 'サトウ', 'ハナコ', '2000-08-20', 2018, 'sato@example.com', NOW(), NOW()),
('田中', '次郎', 'タナカ', 'ジロウ', '1999-12-10', 2018, 'tanaka@example.com', NOW(), NOW());
```

### 学年管理者の登録

```sql
INSERT INTO users (line_id, last_name, first_name, graduation_year, email, role, approval_status, approved_at, created_at, updated_at)
VALUES
('admin_line_id_001', '管理', '太郎', 2018, 'admin2018@example.com', 'year_admin', 'approved', NOW(), NOW(), NOW());
```

### マスター管理者の登録

```sql
INSERT INTO users (line_id, last_name, first_name, graduation_year, email, role, approval_status, approved_at, created_at, updated_at)
VALUES
('master_line_id_001', 'マスター', '管理者', 2020, 'master@example.com', 'master_admin', 'approved', NOW(), NOW(), NOW());
```

## テストシナリオ

### シナリオ1: 完全一致（自動承認）

**入力:**
- 姓: 山田
- 名: 太郎
- 生年月日: 2000-04-15
- 卒業年度: 2018

**期待結果:**
- 即座に承認される
- メール送信なし
- 完了画面に「登録が完了しました」と表示

### シナリオ2: 部分一致（承認待ち）

**入力:**
- 姓: 山田
- 名: 太郎
- 生年月日: 2000-04-16（日付が異なる）
- 卒業年度: 2018

**期待結果:**
- 承認待ち状態になる
- 学年管理者（admin2018@example.com）にメール送信
- 完了画面に「登録申請を受け付けました」と表示

### シナリオ3: 不一致（承認待ち）

**入力:**
- 姓: 鈴木
- 名: 一郎
- 生年月日: 2001-01-01
- 卒業年度: 2019

**期待結果:**
- 承認待ち状態になる
- 2019年の学年管理者がいなければマスター管理者にメール送信
- 完了画面に「登録申請を受け付けました」と表示

## ログ確認

```bash
# アプリケーションログ
docker exec matsu-app cat storage/logs/laravel.log

# メールログ（MAIL_MAILER=logの場合）
docker exec matsu-app tail -f storage/logs/laravel.log | grep "Mailable"
```

## 承認画面の実装（今後の拡張）

管理者用の承認画面を実装する場合、以下のルートを追加：

```php
// routes/web.php
Route::middleware(['auth'])->group(function () {
    Route::get('/admin/approvals', [ApprovalController::class, 'index']);
    Route::post('/admin/approvals/{user}/approve', [ApprovalController::class, 'approve']);
    Route::post('/admin/approvals/{user}/reject', [ApprovalController::class, 'reject']);
});
```

## トラブルシューティング

### メールが送信されない

1. `.env`のメール設定を確認
2. ログを確認: `storage/logs/laravel.log`
3. キューワーカーが動いているか確認（QUEUE_CONNECTION=databaseの場合）

### 承認者が見つからない

ログに以下のメッセージが記録されます：
```
No approver found for user registration
```

対処：該当学年の学年管理者またはマスター管理者を登録してください。
