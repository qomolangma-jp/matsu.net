# ユーザーカテゴリー最適化設計提案

## 現状の問題点

1. **単一値のみ**: `users.user_category` (string型) - 複数カテゴリーの登録不可
2. **検索速度**: フルテキスト検索で遅い
3. **管理の煩雑さ**: カテゴリー名の変更時に全ユーザーのデータ更新が必要
4. **重複データ**: 同じカテゴリー名が複数ユーザーに重複保存

---

## 提案: WordPress型リレーショナル設計

### 1. テーブル構成

#### **categoriesテーブル（カテゴリーマスター）**
```
id (PK)          : bigint unsigned
name             : string(100) UNIQUE  - カテゴリー名（例: 東京地区会）
slug             : string(100) UNIQUE  - URL用スラッグ（例: tokyo）
description      : text NULLABLE       - 説明
type             : enum                - タイプ（district, role, otherなど）
display_order    : integer             - 表示順
is_active        : boolean             - 有効/無効
created_at       : timestamp
updated_at       : timestamp

INDEX: name, slug, type, is_active
```

#### **category_userテーブル（中間テーブル）**
```
id (PK)          : bigint unsigned
user_id (FK)     : bigint unsigned     - users.id への外部キー
category_id (FK) : bigint unsigned     - categories.id への外部キー
assigned_at      : timestamp           - 割り当て日時
assigned_by      : bigint unsigned     - 割り当てた管理者のuser_id
notes            : text NULLABLE       - メモ
created_at       : timestamp
updated_at       : timestamp

UNIQUE KEY: (user_id, category_id)  - 重複防止
INDEX: user_id, category_id
FOREIGN KEY: user_id REFERENCES users(id) ON DELETE CASCADE
FOREIGN KEY: category_id REFERENCES categories(id) ON DELETE CASCADE
```

---

## 2. メリット

### パフォーマンス
- ✅ **高速検索**: インデックス最適化（整数型IDでのJOIN）
- ✅ **複数カテゴリー対応**: 1ユーザーに複数カテゴリー割り当て可能
- ✅ **集計の高速化**: COUNT, GROUP BY が高速

### 保守性
- ✅ **一元管理**: カテゴリー名の変更が1箇所で完結
- ✅ **正規化**: データ重複なし
- ✅ **監査ログ**: assigned_at, assigned_by で履歴追跡

### 拡張性
- ✅ **カテゴリータイプ**: 地区会、役職、その他を type で分類
- ✅ **階層構造**: parent_id を追加すれば階層化可能
- ✅ **メタデータ**: category_user に追加情報を保存可能

---

## 3. Eloquentモデル実装例

### Categoryモデル
```php
class Category extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'type', 'display_order', 'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // 多対多リレーション
    public function users()
    {
        return $this->belongsToMany(User::class, 'category_user')
            ->withTimestamps()
            ->withPivot('assigned_at', 'assigned_by', 'notes');
    }

    // アクティブなカテゴリーのみ
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // タイプで絞り込み
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }
}
```

### Userモデル（既存に追加）
```php
class User extends Authenticatable
{
    // 多対多リレーション
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_user')
            ->withTimestamps()
            ->withPivot('assigned_at', 'assigned_by', 'notes');
    }

    // 地区会のみ取得
    public function districtCategories()
    {
        return $this->categories()->where('type', 'district');
    }

    // カテゴリー名の配列を取得（表示用）
    public function getCategoryNamesAttribute()
    {
        return $this->categories->pluck('name')->toArray();
    }

    // カテゴリーIDでフィルタ（スコープ）
    public function scopeInCategory($query, $categoryId)
    {
        return $query->whereHas('categories', function($q) use ($categoryId) {
            $q->where('categories.id', $categoryId);
        });
    }

    // 複数カテゴリーでフィルタ（OR条件）
    public function scopeInCategories($query, array $categoryIds)
    {
        return $query->whereHas('categories', function($q) use ($categoryIds) {
            $q->whereIn('categories.id', $categoryIds);
        });
    }
}
```

---

## 4. 使用例

### カテゴリー割り当て
```php
// ユーザーにカテゴリーを割り当て
$user->categories()->attach($categoryId, [
    'assigned_at' => now(),
    'assigned_by' => Auth::id(),
    'notes' => '東京地区会に所属'
]);

// 複数カテゴリーを一括割り当て
$user->categories()->sync([1, 3, 5]);

// カテゴリーを削除
$user->categories()->detach($categoryId);
```

### 検索（高速）
```php
// カテゴリーIDでフィルタ
$users = User::inCategory(1)->get();

// 複数カテゴリー（OR条件）
$users = User::inCategories([1, 2, 3])->get();

// カテゴリー名で検索（JOIN使用）
$users = User::whereHas('categories', function($q) {
    $q->where('name', '東京地区会');
})->get();

// カテゴリー数でソート
$users = User::withCount('categories')
    ->orderBy('categories_count', 'desc')
    ->get();
```

### 一覧表示
```php
// Eager Loading（N+1問題回避）
$users = User::with('categories')->paginate(50);

// Blade
@foreach($user->categories as $category)
    <span class="badge bg-info">{{ $category->name }}</span>
@endforeach
```

---

## 5. 移行戦略

### フェーズ1: 新テーブル作成
1. マイグレーション実行（categories, category_user）
2. 既存の user_category データからカテゴリーマスターを作成
3. 既存データを category_user に移行

### フェーズ2: 段階的移行
```php
// 既存データ移行スクリプト
$users = User::whereNotNull('user_category')->get();

foreach ($users as $user) {
    // カテゴリーマスターから検索または作成
    $category = Category::firstOrCreate([
        'name' => $user->user_category,
        'type' => 'district',
    ], [
        'slug' => Str::slug($user->user_category),
        'is_active' => true,
    ]);
    
    // 中間テーブルに登録
    $user->categories()->syncWithoutDetaching([
        $category->id => [
            'assigned_at' => $user->created_at,
            'assigned_by' => null,
        ]
    ]);
}
```

### フェーズ3: 旧カラム削除
1. user_category カラムを非推奨化
2. 十分なテスト期間
3. マイグレーションで user_category カラムを削除

---

## 6. 検索速度比較

### 現状（フルテキストスキャン）
```sql
SELECT * FROM users WHERE user_category = '東京地区会';
-- 実行時間: ~50ms（10,000レコード）
```

### 提案（インデックス付きJOIN）
```sql
SELECT u.* FROM users u
INNER JOIN category_user cu ON u.id = cu.user_id
WHERE cu.category_id = 1;
-- 実行時間: ~5ms（10,000レコード）- 約10倍高速
```

---

## 7. 実装ファイル

### マイグレーション
- `database/migrations/xxxx_create_categories_table.php`
- `database/migrations/xxxx_create_category_user_table.php`
- `database/migrations/xxxx_migrate_user_category_data.php`

### モデル
- `app/Models/Category.php`
- `app/Models/User.php`（リレーション追加）

### コントローラー
- `app/Http/Controllers/Admin/CategoryController.php`（カテゴリー管理）
- `app/Http/Controllers/Admin/UserManagementController.php`（更新）

### ビュー
- `resources/views/admin/categories/index.blade.php`
- `resources/views/admin/categories/create.blade.php`
- `resources/views/admin/users/edit.blade.php`（複数選択UI）

---

## 8. 推奨実装順序

1. ✅ **categoriesテーブル作成** + マイグレーション
2. ✅ **category_userテーブル作成** + マイグレーション
3. ✅ **Categoryモデル作成** + リレーション定義
4. ✅ **Userモデル更新** + リレーション追加
5. ✅ **既存データ移行スクリプト実行**
6. ✅ **カテゴリー管理画面作成** (CRUD)
7. ✅ **ユーザー編集画面更新** (複数選択チェックボックス)
8. ✅ **絞り込み検索更新** (新リレーション使用)
9. ⚠️  **テスト期間** (2-4週間)
10. ✅ **旧user_categoryカラム削除**

---

この設計を実装しますか？実装する場合は、段階的に進めることをお勧めします。
