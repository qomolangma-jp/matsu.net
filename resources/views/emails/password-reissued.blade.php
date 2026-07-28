<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>パスワード再発行のお知らせ</title>
</head>
<body style="margin:0; padding:0; background:#f4f6f8; font-family:'Hiragino Kaku Gothic ProN', 'Yu Gothic', sans-serif; color:#1f2933;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f6f8; padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px; background:#ffffff; border-radius:10px; overflow:hidden;">
                    <tr>
                        <td style="background:#2c5f2d; color:#ffffff; padding:18px 24px; font-size:20px; font-weight:700;">
                            松高.net パスワード再発行
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px; font-size:15px; line-height:1.8;">
                            <p style="margin-top:0;">{{ $user->full_name ?? ($user->last_name . ' ' . $user->first_name) }} 様</p>
                            <p>パスワード再発行の申請を受け付けました。<br>新しいパスワードは以下のとおりです。</p>

                            <div style="background:#f0f7f0; border:1px solid #d6e5d6; border-radius:8px; padding:16px; margin:16px 0 20px;">
                                <div style="font-size:13px; color:#4b5563; margin-bottom:6px;">再発行パスワード</div>
                                <div style="font-size:22px; font-weight:700; letter-spacing:1px; color:#111827;">{{ $newPassword }}</div>
                            </div>

                            <p style="margin-bottom:8px;">セキュリティのため、ログイン後にパスワード変更画面から必ず任意のパスワードへ変更してください。</p>
                            <p style="margin:0;">
                                ログイン画面: <a href="{{ $loginUrl }}">{{ $loginUrl }}</a><br>
                                パスワード変更画面: <a href="{{ $passwordChangeUrl }}">{{ $passwordChangeUrl }}</a>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 24px; background:#f8fafc; font-size:12px; color:#6b7280; line-height:1.6;">
                            このメールに心当たりがない場合は破棄してください。<br>
                            本メールは送信専用です。返信には対応していません。
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
