<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LineIdTokenVerifier
{
    /**
     * LIFFのIDトークンをLINEのサーバーで検証し、LINEユーザーID(sub)を返す。失敗時はnull。
     */
    public function verify(?string $idToken, ?string $expectedNonce = null): ?string
    {
        $channelId = config('services.line.login_channel_id');

        if (!$idToken || !$channelId) {
            Log::warning('LINE IDトークン検証をスキップ（トークンまたはチャネルID未設定）', [
                'has_token' => (bool) $idToken,
                'has_channel_id' => (bool) $channelId,
            ]);
            return null;
        }

        try {
            $response = Http::asForm()->timeout(10)->post('https://api.line.me/oauth2/v2.1/verify', [
                'id_token' => $idToken,
                'client_id' => $channelId,
            ]);
        } catch (\Throwable $e) {
            Log::error('LINE IDトークン検証の通信に失敗: ' . $e->getMessage());
            return null;
        }

        if (!$response->successful()) {
            Log::warning('LINE IDトークン検証に失敗', ['status' => $response->status()]);
            return null;
        }

        if ($expectedNonce !== null && !hash_equals($expectedNonce, (string) $response->json('nonce', ''))) {
            Log::warning('LINE IDトークンのnonceが一致しません');
            return null;
        }

        $sub = $response->json('sub');

        return is_string($sub) && $sub !== '' ? $sub : null;
    }
}