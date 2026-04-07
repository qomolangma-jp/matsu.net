<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LINEログイン処理中...</title>
    <style>
        body { font-family: sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; background: #f0f0f0; }
        .box { text-align: center; color: #555; }
        .spinner { width: 40px; height: 40px; border: 4px solid #ddd; border-top-color: #2c5f2d; border-radius: 50%; animation: spin 0.8s linear infinite; margin: 0 auto 16px; }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="box">
        <div class="spinner"></div>
        <p>LINEログイン処理中...</p>
    </div>
    <script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
    <script>
        liff.init({ liffId: '{{ $liffId }}' })
            .catch(function(err) {
                document.querySelector('.box').innerHTML =
                    '<p style="color:#c0392b">ログインエラー: ' + err.message + '</p>' +
                    '<a href="/register">登録ページへ戻る</a>';
            });
        // liff.init() が liff.state の /register へ自動リダイレクトする
    </script>
</body>
</html>
