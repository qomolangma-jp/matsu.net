@echo off
echo ========================================
echo   松.net セットアップスクリプト
echo ========================================
echo.

echo [1/6] .envファイルの作成...
if not exist .env (
    copy .env.example .env
    echo .envファイルを作成しました
) else (
    echo .envファイルは既に存在します
)
echo.

echo [2/6] Dockerコンテナの起動...
docker-compose up -d
echo.

echo [3/6] Composerのインストール（数分かかる場合があります）...
docker exec -it pg-1geki-app composer install --no-interaction
echo.

echo [4/6] アプリケーションキーの生成...
docker exec -it pg-1geki-app php artisan key:generate --force
echo.

echo [5/6] データベースマイグレーション...
timeout /t 5 /nobreak > nul
docker exec -it pg-1geki-app php artisan migrate --force
echo.

echo [6/6] 権限設定...
docker exec -it pg-1geki-app chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
echo.

echo ========================================
echo   セットアップ完了！
echo ========================================
echo.
echo 以下のURLでアクセスできます：
echo   - メインアプリ: http://matsu.localhost
echo   - phpMyAdmin:   http://localhost:8080
echo.
echo ※初回アクセス時にエラーが出る場合は、
echo   数分待ってから再度アクセスしてください。
echo.
echo hostsファイルの設定を忘れずに：
echo   C:\Windows\System32\drivers\etc\hosts に
echo   127.0.0.1 matsu.localhost を追加
echo.
pause
