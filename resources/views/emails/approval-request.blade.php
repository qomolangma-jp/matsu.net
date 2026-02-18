<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>新規登録承認依頼</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Arial, 'Hiragino Kaku Gothic ProN', 'Hiragino Sans', Meiryo, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #2c5f2d;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 30px;
            border: 1px solid #ddd;
            border-radius: 0 0 5px 5px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background-color: white;
        }
        .info-table th,
        .info-table td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }
        .info-table th {
            background-color: #f5f5f5;
            font-weight: bold;
            width: 35%;
        }
        .match-status {
            padding: 10px;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
        }
        .match-partial {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
        }
        .match-none {
            background-color: #f8d7da;
            border-left: 4px solid #dc3545;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #2c5f2d;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 5px;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>松.net 新規登録承認依頼</h2>
    </div>
    
    <div class="content">
        <p>
            @if(in_array($approverRole ?? '', ['year_admin', 'grade_admin']))
                学年管理者様
            @elseif(in_array($approverRole ?? '', ['master_admin', 'master']))
                マスター管理者様
            @else
                管理者様
            @endif
        </p>
        
        <p>松.netに新規登録申請がありました。以下の内容をご確認の上、承認・却下のご判断をお願いいたします。</p>
        
        @if($matchType === 'exact')
            <div class="match-status" style="background-color: #d4edda; border-left: 4px solid #28a745;">
                ✓ 完全一致：参照名簿と完全に一致しました（自動承認済み）
            </div>
        @elseif($matchType === 'kana')
            <div class="match-status match-partial">
                ⚠️ カナ一致：参照名簿とフリガナが一致しています
            </div>
        @elseif($matchType === 'partial')
            <div class="match-status match-partial">
                ⚠️ 部分一致：参照名簿と一部の情報が一致しています
            </div>
        @elseif($matchType === 'multiple')
            <div class="match-status match-partial">
                ⚠️ 複数該当：参照名簿に複数の該当者が見つかりました
            </div>
        @elseif($matchType === 'none')
            <div class="match-status match-none">
                ❌ 不一致：参照名簿に該当する情報が見つかりませんでした
            </div>
        @endif
        
        <h3>申請者情報</h3>
        <table class="info-table">
            <tr>
                <th>氏名</th>
                <td>{{ $user->last_name }} {{ $user->first_name }}</td>
            </tr>
            @if($user->last_name_kana || $user->first_name_kana)
            <tr>
                <th>フリガナ</th>
                <td>{{ $user->last_name_kana }} {{ $user->first_name_kana }}</td>
            </tr>
            @endif
            <tr>
                <th>生年月日</th>
                <td>{{ $user->birth_date?->format('Y年m月d日') }}</td>
            </tr>
            <tr>
                <th>卒業年度</th>
                <td>{{ $user->graduation_year }}年</td>
            </tr>
            @if($user->email)
            <tr>
                <th>メールアドレス</th>
                <td>{{ $user->email }}</td>
            </tr>
            @endif
            @if($user->address)
            <tr>
                <th>住所</th>
                <td>〒{{ $user->postal_code }}<br>{{ $user->address }}</td>
            </tr>
            @endif
        </table>
        
        @if(!empty($matchedData))
        <h3>参照名簿の該当情報</h3>
        <table class="info-table">
            <tr>
                <th>卒業回</th>
                <td>{{ $matchedData['graduation_term'] ?? '' }}</td>
            </tr>
            <tr>
                <th>氏名</th>
                <td>{{ $matchedData['name'] ?? '' }}</td>
            </tr>
            @if(isset($matchedData['kana']))
            <tr>
                <th>フリガナ</th>
                <td>{{ $matchedData['kana'] }}</td>
            </tr>
            @endif
            @if(isset($matchedData['gender']))
            <tr>
                <th>性別</th>
                <td>{{ $matchedData['gender'] }}</td>
            </tr>
            @endif
            @if(isset($matchedData['postal_code']) || isset($matchedData['address_1']))
            <tr>
                <th>住所</th>
                <td>
                    @if(isset($matchedData['postal_code']))〒{{ $matchedData['postal_code'] }}<br>@endif
                    {{ $matchedData['address_1'] ?? '' }}
                    {{ $matchedData['address_2'] ?? '' }}
                    {{ $matchedData['address_3'] ?? '' }}
                </td>
            </tr>
            @endif
        </table>
        @endif
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $approvalUrl }}" class="button" style="background-color: #2c5f2d;">
                📋 承認画面を開く
            </a>
        </div>
        
        <p style="font-size: 14px; color: #666; background-color: #fff; padding: 15px; border-left: 3px solid #2c5f2d;">
            <strong>承認手順：</strong><br>
            1. 上記「承認画面を開く」ボタンをクリック<br>
            2. 管理画面にログイン（必要な場合）<br>
            3. 申請者の詳細を確認<br>
            4. 「承認」または「却下」ボタンをクリック
        </p>
        
        <p style="font-size: 14px; color: #666;">
            ※このメールは自動送信されています。<br>
            ※返信はできませんので、ご了承ください。
        </p>
    </div>
    
    <div class="footer">
        <p>
            松.net 同窓生向けWebシステム<br>
            {{ config('app.url') }}
        </p>
    </div>
</body>
</html>
