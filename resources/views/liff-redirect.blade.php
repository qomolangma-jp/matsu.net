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

        var msgEl = document.getElementById('msg');
        function log(txt) {
            msgEl.innerHTML += txt + '<br>';
        }

        // liff.init() 前に liff.state を取得する（init後にURLが書き換わるため）
        var _rawState = new URLSearchParams(window.location.search).get('liff.state');
        var destPath  = (_rawState && /^\/(events|news)\/\d+$/.test(_rawState)) ? _rawState : null;

        log('destPath: ' + destPath);
        log('IS_LOGGED_IN: ' + IS_LOGGED_IN);

        liff.init({ liffId: LIFF_ID })
            .then(function () {
                log('liff.isLoggedIn: ' + liff.isLoggedIn());

                // ログイン済みなら直接遷移
                if (IS_LOGGED_IN) {
                    window.location.replace(destPath || '/mypage');
                    return;
                }

                // 未ログイン：LIFFセッションがあればLINE IDを取得してサーバー認証
                if (liff.isLoggedIn()) {
                    log('getProfile呼び出し中...');
                    return liff.getProfile()
                        .then(function (profile) {
                            log('LINE ID取得: ' + profile.userId.substring(0, 6) + '...');
                            var lineId   = profile.userId;
                            var redirect = destPath || '/mypage';
                            window.location.replace(
                                '/auth/line?line_id=' + encodeURIComponent(lineId)
                                + '&redirect=' + encodeURIComponent(redirect)
                            );
                        });
                } else {
                    log('LIFF未ログイン → liff.login()起動');
                    liff.login();
                }
            })
            .catch(function (err) {
                document.querySelector('.box p').textContent = 'エラーが発生しました';
                log('ERROR: ' + err.message);
            });
    </script>
</body>
</html>
