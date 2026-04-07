<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>松.net</title>
    <style>
        body { font-family: sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; background: #f0f0f0; }
        .box { text-align: center; color: #555; padding: 20px; }
        .spinner { width: 40px; height: 40px; border: 4px solid #ddd; border-top-color: #2c5f2d; border-radius: 50%; animation: spin 0.8s linear infinite; margin: 0 auto 16px; }
        @keyframes spin { to { transform: rotate(360deg); } }
        #msg { font-size: 0.85em; margin-top: 8px; }
    </style>
</head>
<body>
    <div class="box">
        <div class="spinner"></div>
        <p>読み込み中...</p>
        <div id="msg"></div>
    </div>
    <script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
    <script>
        var LIFF_ID = '{{ $liffId }}';

        liff.init({ liffId: LIFF_ID })
            .then(function () {
                // init 完了 → /register へ移動
                // liff.state がある場合は SDK が自動で遷移する
                // ない場合（LINE 内ブラウザ直接アクセス等）は手動で飛ばす
                window.location.replace('/register');
            })
            .catch(function (err) {
                document.querySelector('.box p').textContent = 'エラーが発生しました';
                document.getElementById('msg').textContent = err.message;
                setTimeout(function () {
                    window.location.replace('/register');
                }, 3000);
            });
    </script>
</body>
</html>
