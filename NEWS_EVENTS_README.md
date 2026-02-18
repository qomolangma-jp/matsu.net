# ニュース送信・出欠管理機能 実装ガイド

## 📋 実装概要

管理者用の「ニュース送信」および「出欠管理」機能を実装しました。

### 実装した機能

#### 1. ニュース送信機能
- ニュース一覧・作成・編集・削除
- 対象卒業年度の選択（特定学年または全学年）
- LINEで通知する（ON/OFF）
- TOPページに掲載する（ON/OFF）
- LINE Messaging API連携（ダミー実装）

#### 2. 出欠管理機能
- イベント一覧・作成・編集・削除
- イベント詳細・出欠状況確認
- 出欠データCSVエクスポート
- 募集締切・定員管理
- マスター管理者：全体同窓会を管理
- 学年管理者：自学年のイベントを管理

## 📁 作成・変更したファイル

### モデル
- `app/Models/News.php` - ニュースモデル
- `app/Models/Event.php` - イベントモデル
- `app/Models/Attendance.php` - 出欠モデル

### コントローラー
- `app/Http/Controllers/Admin/NewsController.php` - ニュース管理
- `app/Http/Controllers/Admin/EventController.php` - イベント・出欠管理

### サービス
- `app/Services/LineMessagingService.php` - LINE Messaging API（ダミー実装）

### ビュー（ニュース管理）
- `resources/views/admin/news/index.blade.php` - ニュース一覧
- `resources/views/admin/news/create.blade.php` - ニュース作成
- `resources/views/admin/news/edit.blade.php` - ニュース編集

### ビュー（イベント管理）
- `resources/views/admin/events/index.blade.php` - イベント一覧
- `resources/views/admin/events/create.blade.php` - イベント作成
- `resources/views/admin/events/edit.blade.php` - イベント編集
- `resources/views/admin/events/show.blade.php` - 出欠状況確認

### 設定・ルート
- `config/services.php` - LINE APIトークン設定
- `routes/web.php` - ニュース・イベント管理ルート追加
- `resources/views/layouts/admin.blade.php` - サイドバーナビゲーション更新

## 🚀 アクセス方法

### 1. テストログイン
```
http://matsu.localhost/test-login
```

以下のいずれかでログイン：
- **マスター管理者**: master@matsu.localhost（全学年管理可能）
- **学年管理者（2018年）**: admin2018@matsu.localhost
- **学年管理者（2019年）**: admin2019@matsu.localhost

### 2. ニュース管理
```
http://matsu.localhost/admin/news
```

**機能：**
- ニュース一覧表示
- 新規ニュース作成（タイトル、本文、対象学年）
- LINE通知ON/OFF
- TOPページ掲載ON/OFF
- ニュース編集・削除

**権限：**
- マスター管理者：全学年のニュースを管理可能
- 学年管理者：自学年のニュースのみ管理可能

### 3. イベント管理
```
http://matsu.localhost/admin/events
```

**機能：**
- イベント一覧表示
- 新規イベント作成（タイトル、内容、日時、場所、締切、定員）
- 対象学年選択（全体同窓会 or 特定学年）
- イベント編集・削除

**権限：**
- マスター管理者：全体同窓会、各学年イベントすべて管理可能
- 学年管理者：自学年のイベントのみ管理可能

### 4. 出欠状況確認
```
http://matsu.localhost/admin/events/{イベントID}
```

**機能：**
- 統計情報表示（対象者数、出席、欠席、未回答）
- 回答率プログレスバー
- 出席者・欠席者・未回答者のタブ表示
- CSVエクスポート

## 📊 データベース構造

### newsテーブル
| カラム | 型 | 説明 |
|--------|-----|------|
| id | bigint | 主キー |
| title | varchar(255) | タイトル |
| body | text | 本文 |
| target_graduation_years | json | 対象卒業年度（配列） |
| is_line_notification | boolean | LINE通知済みフラグ |
| is_top_display | boolean | TOP掲載フラグ |
| published_at | timestamp | 公開日時 |
| created_by | bigint | 作成者ID |
| created_at | timestamp | 作成日時 |
| updated_at | timestamp | 更新日時 |
| deleted_at | timestamp | 削除日時 |

### eventsテーブル
| カラム | 型 | 説明 |
|--------|-----|------|
| id | bigint | 主キー |
| title | varchar(255) | イベント名 |
| description | text | イベント内容 |
| event_date | timestamp | 開催日時 |
| event_location | varchar(255) | 開催場所 |
| registration_deadline | timestamp | 募集締切 |
| max_participants | integer | 定員 |
| target_graduation_year | integer | 対象学年（NULL=全体） |
| is_published | boolean | 公開フラグ |
| created_by | bigint | 作成者ID |
| created_at | timestamp | 作成日時 |
| updated_at | timestamp | 更新日時 |
| deleted_at | timestamp | 削除日時 |

### attendancesテーブル
| カラム | 型 | 説明 |
|--------|-----|------|
| id | bigint | 主キー |
| event_id | bigint | イベントID |
| user_id | bigint | ユーザーID |
| status | varchar(50) | ステータス（attending/absent） |
| note | text | 備考 |
| created_at | timestamp | 回答日時 |
| updated_at | timestamp | 更新日時 |

## 🔧 LINE Messaging API設定

### 環境変数（.env）
```env
# LINE Messaging API
LINE_MESSAGING_CHANNEL_ACCESS_TOKEN=your_channel_access_token_here
LIFF_ID=your_liff_id_here
```

### ダミー実装について

現在、LINE Messaging APIは**ダミー実装**となっています。

**開発環境（local）での動作：**
- LINE送信処理は実行されず、ログに記録のみ
- 常に成功として扱われる
- `storage/logs/laravel.log` でログ確認可能

**本番環境での動作：**
- `LINE_MESSAGING_CHANNEL_ACCESS_TOKEN` が設定されている場合、実際にAPIを呼び出す
- 未設定の場合はエラーをスロー

### 本番環境で実際にLINE送信を有効化する手順

1. LINE Developersコンソールでチャネル作成
2. Channel Access Tokenを取得
3. `.env`に設定
   ```env
   LINE_MESSAGING_CHANNEL_ACCESS_TOKEN=実際のトークン
   ```
4. `app/Services/LineMessagingService.php`の`sendPushMessage()`メソッドが実際のAPIを呼び出す

## 📝 使用例

### ニュース送信

1. 管理画面にログイン
2. サイドバーから「ニュース管理」をクリック
3. 「新規作成」ボタンをクリック
4. タイトル・本文を入力
5. 対象卒業年度を選択（未選択=全学年）
6. 「LINEで通知する」をON
7. 「TOPページに掲載する」をON（任意）
8. 「作成する」をクリック

→ 対象ユーザーにLINEメッセージが送信されます（開発環境ではログのみ）

### イベント作成・出欠確認

1. 管理画面にログイン
2. サイドバーから「イベント管理」をクリック
3. 「新規作成」ボタンをクリック
4. イベント情報を入力
   - イベント名：「第75回期同窓会」
   - 開催日時：2026-06-15 18:00
   - 開催場所：「東京プリンスホテル」
   - 募集締切：2026-06-01
   - 定員：100
   - 対象学年：2018年（学年管理者の場合は自動設定）
5. 「LINEで通知する」をON
6. 「作成する」をクリック

→ イベントが作成され、LINEで通知されます

7. イベント一覧から「出欠確認」アイコンをクリック
8. 出席・欠席・未回答の状況を確認
9. 必要に応じてCSVダウンロード

## ⚠️ 注意事項

### 権限管理
- **マスター管理者**：すべてのニュース・イベントを管理可能
- **学年管理者**：自学年のニュース・イベントのみ管理可能
- 他学年のデータへのアクセスは自動的にブロックされます

### データの削除
- ニュース・イベントの削除は論理削除（SoftDeletes）
- 実際のレコードはデータベースに残ります
- イベントを削除すると、関連する出欠データもすべて削除されます

### LINE通知
- 通知は作成時に1回のみ送信されます
- 編集しても再送信されません
- 未公開（下書き）の状態ではLINE通知は送信されません

### CSVエクスポート
- UTF-8 BOM付きでExcel対応
- 絞り込み条件がそのまま適用されます
- 出欠データには備考欄も含まれます

## 🔍 トラブルシューティング

### ニュースが作成できない
- 管理者権限があるか確認
- 学年管理者の場合、自学年が選択されているか確認
- すべての必須項目が入力されているか確認

### イベント一覧に表示されない
- マスター管理者でログインしているか確認
- 学年管理者の場合、自学年のイベントか確認
- 公開/下書きのフィルタを確認

### LINE通知が送信されない
- 開発環境では実際には送信されません（ログのみ）
- `storage/logs/laravel.log`でログを確認
- 本番環境では`LINE_MESSAGING_CHANNEL_ACCESS_TOKEN`が設定されているか確認

### CSVダウンロードができない
- 権限があるか確認
- ブラウザのポップアップブロックを解除
- ファイル名に日本語が含まれる場合、一部ブラウザで文字化けする可能性あり

## 📈 今後の拡張案

- [ ] ニュースのプレビュー機能
- [ ] イベント参加者への個別メッセージ送信
- [ ] 出欠回答のリマインド機能
- [ ] イベント参加費の管理
- [ ] Flex Messageを使った高度なLINE通知
- [ ] 画像添付機能
- [ ] ニュース・イベントの承認フロー
- [ ] 統計ダッシュボード

## 📚 関連ファイル

- [管理画面アクセス手順](ADMIN_LOGIN.md)
- [データベース設計](database/migrations/)
- [LINE Messaging API公式ドキュメント](https://developers.line.biz/ja/docs/messaging-api/)
