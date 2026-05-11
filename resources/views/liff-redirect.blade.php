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
        var LIFF_ID      = '{{ $liffId }}';
        var IS_LOGGED_IN = {{ Auth::check() ? 'true' : 'false' }};

        // liff.init() 前に liff.state を取得する（init後にURLが書き換わるため）
        var _rawState = new URLSearchParams(window.location.search).get('liff.state');
        var destPath  = (_rawState && /^\/(events|news)\/\d+$/.test(_rawState)) ? _rawState : null;

        liff.init({ liffId: LIFF_ID })
            .then(function () {
                // ログイン済みなら直接遷移
                if (IS_LOGGED_IN) {
                    window.location.replace(destPath || '/mypage');
                    return;
                }

                // 未ログイン：LIFFセッションがあればLINE IDを取得してサーバー認証
                if (liff.isLoggedIn()) {
                    return liff.getProfile()
                        .then(function (profile) {
                            var lineId   = profile.userId;
                            var redirect = destPath || '/mypage';
                            window.location.replace(
                                '/auth/line?line_id=' + encodeURIComponent(lineId)
                                + '&redirect=' + encodeURIComponent(redirect)
                            );
                        });
                } else {
                    // LIFFログインも未済（外部ブラウザ等）
                    window.location.replace(destPath || '/mypage');
                }
            })
            .catch(function (err) {
                document.querySelector('.box p').textContent = 'エラーが発生しました';
                document.getElementById('msg').textContent = err.message;
                setTimeout(function () {
                    window.location.replace('/mypage');
                }, 3000);
            });
    </script>
</body>
</html>
