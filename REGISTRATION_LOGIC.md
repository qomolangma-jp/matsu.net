# 新規登録処理と名簿照合ロジック - 実装完了

## 📋 実装内容

### 1. FormRequestクラス（バリデーション分離）
**ファイル**: [app/Http/Requests/RegisterRequest.php](app/Http/Requests/RegisterRequest.php)

#### 主要機能
- **バリデーションルール定義**
  - LINE ID（必須、ユニーク）
  - 姓・名（必須）
  - 生年月日（必須、今日より前）
  - 卒業年度（例: "高校51回期"）
  - メールアドレス、郵便番号、住所（任意）
  
- **検索用氏名の正規化**
  ```php
  public function getSearchName(): string
  {
      // 全角・半角スペース、タブ、改行を除去
      // 例: "相河 奈美" → "相河奈美"
      return preg_replace('/[\s　]+/u', '', $lastName . $firstName);
  }
  ```

- **卒業年度の数値変換**
  ```php
  public function getGraduationYear(): ?int
  {
      // "高校51回期" → 2018年
      // 計算式: 1967 + 51 = 2018
  }
  ```

---

### 2. RegisterController（登録処理）
**ファイル**: [app/Http/Controllers/RegisterController.php](app/Http/Controllers/RegisterController.php)

#### メソッド: `store(RegisterRequest $request)`
**処理フロー**：
1. 卒業年度を数値に変換
2. 参照名簿と照合（`matchWithReferenceRoster()`）
3. ユーザー作成（`approval_status` = 'approved' or 'pending'）
4. 完全一致の場合 → 自動承認、登録済みフラグ更新
5. 保留の場合 → 承認依頼メール送信
6. オートログイン
7. 完了画面にリダイレクト

---

### 3. 名簿照合ロジック（重要）
**メソッド**: `matchWithReferenceRoster(RegisterRequest $request)`

#### 照合手順（優先順位順）

##### ① 完全一致チェック（自動承認）
```sql
-- 卒業回 + 氏名（スペース無視）が1件のみ該当
WHERE graduation_term = '高校51回期'
  AND REPLACE(REPLACE(REPLACE(name, ' ', ''), '　', ''), '\t', '') = '相河奈美'
```
- **該当1件** → `status: 'approved'` 自動承認
- **該当複数** → `status: 'pending'` 保留（複数該当）
- **該当なし** → 次のチェックへ

##### ② カナ一致チェック（保留扱い）
```sql
-- 卒業回 + フリガナ（スペース無視）が1件該当
WHERE graduation_term = '高校51回期'
  AND REPLACE(REPLACE(REPLACE(kana, ' ', ''), '　', ''), '\t', '') = 'ｱｲｶﾜﾅﾐ'
```
- **該当1件** → `status: 'pending'` 保留（カナ一致）
- **該当なし** → 次のチェックへ

##### ③ 不一致（保留扱い）
- 該当レコードなし → `status: 'pending'` 保留（該当なし）

#### 戻り値
```php
return [
    'status' => 'approved' or 'pending',
    'match_type' => 'exact' | 'multiple' | 'kana' | 'none',
    'matched_roster' => ReferenceRoster|null,
];
```

---

### 4. 承認依頼メール送信
**メソッド**: `sendApprovalRequest(User $user, array $matchResult)`

#### 承認者の検索順序

##### 優先順位1: 学年管理者
```php
User::where('graduation_year', $user->graduation_year)
    ->whereIn('role', ['year_admin', 'grade_admin'])
    ->where('approval_status', 'approved')
    ->whereNotNull('email')
    ->first();
```

##### 優先順位2: マスター管理者
```php
User::whereIn('role', ['master_admin', 'master'])
    ->where('approval_status', 'approved')
    ->whereNotNull('email')
    ->first();
```

#### メール送信（非同期キュー対応）
```php
Mail::to($approver->email)->send(
    new ApprovalRequestMail(
        $user,              // 申請者
        $matchResult['match_type'], // 'exact' | 'multiple' | 'kana' | 'none'
        $matchedData,       // 参照名簿データ
        $approver->role     // 承認者の権限
    )
);
```

---

### 5. ApprovalRequestMail（非同期メール）
**ファイル**: [app/Mail/ApprovalRequestMail.php](app/Mail/ApprovalRequestMail.php)

#### 変更点
- `implements ShouldQueue` を追加（非同期キュー対応）
- `$approverRole` パラメータを追加
- `$approvalUrl` を生成して承認画面URLをメール本文に含める

```php
$approvalUrl = route('admin.users.index', [
    'approval_status' => 'pending',
    'user_id' => $this->user->id,
]);
```

---

### 6. メールテンプレート
**ファイル**: [resources/views/emails/approval-request.blade.php](resources/views/emails/approval-request.blade.php)

#### 改善点
- 承認者の役割に応じた挨拶（学年管理者様/マスター管理者様）
- 照合結果の表示（完全一致/カナ一致/部分一致/複数該当/不一致）
- **承認画面URL**のボタン追加
- 承認手順の明記
- 参照名簿データの表示（新構造: `graduation_term`, `name`, `kana`, `address_1/2/3`）

---

## 🔍 実装のポイント

### スペース除去による照合精度向上
```php
// 入力値の正規化（FormRequest）
$searchName = preg_replace('/[\s　]+/u', '', $lastName . $firstName);

// DB側の正規化（SQL）
REPLACE(REPLACE(REPLACE(name, ' ', ''), '　', ''), '\t', '')
```

### 完全一致の判定条件
- 卒業回が完全一致
- 氏名がスペースを除去して完全一致
- **該当レコードが1件のみ** ← 重要！

### 複数該当の扱い
- 複数該当した場合は自動承認せず、保留扱い
- 管理者が手動で確認する

### ログ記録
```php
Log::info('参照名簿照合開始', [
    'graduation_term' => $graduationTerm,
    'search_name' => $searchName,
]);

Log::info('参照名簿と完全一致（自動承認）', [
    'matched_id' => $matched->id,
]);
```

---

## 📊 処理フロー図

```
LIFF登録画面
    ↓
RegisterRequest::validated()
    ↓（バリデーションOK）
RegisterController::store()
    ↓
卒業年度変換（高校51回期 → 2018）
    ↓
matchWithReferenceRoster()
    ├─ 完全一致（1件のみ）
    │   ├─ status: 'approved'
    │   ├─ 自動承認処理
    │   └─ 登録済みフラグ更新
    │
    ├─ 複数該当
    │   ├─ status: 'pending'
    │   └─ sendApprovalRequest()
    │
    ├─ カナ一致
    │   ├─ status: 'pending'
    │   └─ sendApprovalRequest()
    │
    └─ 該当なし
        ├─ status: 'pending'
        └─ sendApprovalRequest()
            ↓
承認者検索（学年管理者 → マスター管理者）
            ↓
ApprovalRequestMail送信（非同期キュー）
            ↓
オートログイン
            ↓
完了画面
```

---

## 🧪 動作確認

### テストデータ準備
```bash
# サンプルCSVインポート済み
docker exec matsu-app php artisan rosters:show --term=51
```

### 登録テスト
1. http://matsu.localhost/register にアクセス
2. 以下を入力：
   - LINE ID: `test_user_001`
   - 姓: `相河`、名: `奈美`
   - 生年月日: `1990-01-01`
   - 卒業年度: `高校51回期`
   - メール: `test@example.com`
3. 送信 → 完全一致で自動承認される
4. ログ確認:
   ```bash
   docker exec matsu-app tail -f storage/logs/laravel.log
   ```

---

## ⚙️ 設定

### キュー設定（非同期メール送信）
**ファイル**: `.env`
```env
QUEUE_CONNECTION=database  # または redis
```

### キューワーカー起動
```bash
docker exec matsu-app php artisan queue:work
```

### 卒業年度変換式の調整
**ファイル**: [app/Http/Requests/RegisterRequest.php](app/Http/Requests/RegisterRequest.php#L124)
```php
// 基準年度の調整（実際の卒業回の規則に合わせる）
return 1967 + $termNumber;
```

---

## 📁 更新ファイル一覧

1. ✅ [app/Http/Requests/RegisterRequest.php](app/Http/Requests/RegisterRequest.php) - 新規作成
2. ✅ [app/Http/Controllers/RegisterController.php](app/Http/Controllers/RegisterController.php) - `store()`, `matchWithReferenceRoster()`, `sendApprovalRequest()` を改修
3. ✅ [app/Mail/ApprovalRequestMail.php](app/Mail/ApprovalRequestMail.php) - `ShouldQueue`, `$approvalUrl` を追加
4. ✅ [resources/views/emails/approval-request.blade.php](resources/views/emails/approval-request.blade.php) - 承認画面URL、承認手順を追加
5. ✅ [routes/web.php](routes/web.php) - `register` → `store` メソッド名変更

---

## 🎯 要件の対応状況

### ✅ 要件1: バリデーションと正規化
- FormRequestクラスで分離
- スペース除去による検索用氏名作成
- DB側もREPLACE関数でスペース除去して照合

### ✅ 要件2: 名簿照合ロジック
- 完全一致（1件のみ） → 自動承認（`status: 'approved'`）
- 複数該当/カナ一致/該当なし → 保留（`status: 'pending'`）+ メール送信

### ✅ 要件3: 承認依頼メール送信
- 優先順位: 学年管理者 → マスター管理者
- 非同期キュー対応（`ShouldQueue`）
- メール本文に申請者情報、卒業年度、承認画面URLを含める

---

## 📚 関連ドキュメント

- [CSV_IMPORT.md](CSV_IMPORT.md) - 参照名簿インポート手順
- [Laravel Mail Documentation](https://laravel.com/docs/11.x/mail)
- [Laravel Queue Documentation](https://laravel.com/docs/11.x/queues)
