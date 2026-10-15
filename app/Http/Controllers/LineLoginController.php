<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\LineIdTokenVerifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LineLoginController extends Controller
{
    public function redirectToLine(Request $request)
    {
        $channelId = config('services.line.login_channel_id');
        $channelSecret = config('services.line.login_channel_secret');

        if (!$channelId || !$channelSecret) {
            Log::error('PC向けLINEログイン設定が不足しています');

            return redirect()->route('admin.login.form')
                ->with('error', 'LINEログインを利用できません。管理者にお問い合わせください。');
        }

        $state = Str::random(40);
        $nonce = Str::random(40);
        $request->session()->put([
            'line_login_state' => $state,
            'line_login_nonce' => $nonce,
            'line_login_redirect' => $this->safeRedirectPath((string) $request->query('redirect', '/mypage')),
        ]);

        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => $channelId,
            'redirect_uri' => $this->redirectUri(),
            'state' => $state,
            'scope' => 'openid',
            'nonce' => $nonce,
        ]);

        return redirect()->away('https://access.line.me/oauth2/v2.1/authorize?' . $query);
    }

    public function handleCallback(Request $request, LineIdTokenVerifier $verifier)
    {
        $expectedState = (string) $request->session()->pull('line_login_state', '');
        $nonce = (string) $request->session()->pull('line_login_nonce', '');
        $redirectPath = (string) $request->session()->pull('line_login_redirect', '/mypage');

        if (!$expectedState || !$nonce || !hash_equals($expectedState, (string) $request->query('state', ''))) {
            Log::warning('PC向けLINEログインのstate検証に失敗', ['ip' => $request->ip()]);

            return redirect()->route('admin.login.form')
                ->with('error', 'LINEログインの有効期限が切れたか、認証を確認できませんでした。もう一度お試しください。');
        }

        if ($request->filled('error')) {
            return redirect()->route('admin.login.form')
                ->with('error', 'LINEログインがキャンセルされました。');
        }

        $channelId = config('services.line.login_channel_id');
        $channelSecret = config('services.line.login_channel_secret');
        $code = $request->query('code');

        if (!$channelId || !$channelSecret || !is_string($code) || $code === '') {
            Log::warning('PC向けLINEログインのコールバック情報が不足しています', [
                'has_channel_id' => (bool) $channelId,
                'has_channel_secret' => (bool) $channelSecret,
            ]);

            return redirect()->route('admin.login.form')
                ->with('error', 'LINE認証を完了できませんでした。もう一度お試しください。');
        }

        try {
            $response = Http::asForm()->timeout(10)->post('https://api.line.me/oauth2/v2.1/token', [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $this->redirectUri(),
                'client_id' => $channelId,
                'client_secret' => $channelSecret,
            ]);
        } catch (\Throwable $e) {
            Log::error('LINEログインのトークン交換に失敗', ['error' => $e->getMessage()]);

            return redirect()->route('admin.login.form')
                ->with('error', 'LINEとの通信に失敗しました。時間をおいて再度お試しください。');
        }

        if (!$response->successful()) {
            Log::warning('LINEログインのトークン交換が拒否されました', ['status' => $response->status()]);

            return redirect()->route('admin.login.form')
                ->with('error', 'LINE認証を完了できませんでした。もう一度お試しください。');
        }

        $lineId = $verifier->verify($response->json('id_token'), $nonce);
        if (!$lineId) {
            return redirect()->route('admin.login.form')
                ->with('error', 'LINEアカウントを確認できませんでした。もう一度お試しください。');
        }

        $user = User::where('line_id', $lineId)->first();
        if (!$user) {
            $request->session()->regenerate();
            $request->session()->put('verified_line_id', $lineId);

            return redirect()->route('register.form');
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        if (in_array($user->role, ['master_admin', 'year_admin'], true)) {
            return redirect()->intended(route('admin.users.index'));
        }

        return redirect()->intended($this->safeRedirectPath($redirectPath));
    }

    private function redirectUri(): string
    {
        return config('services.line.login_redirect_uri') ?: route('line.login.callback');
    }

    private function safeRedirectPath(string $path): string
    {
        if ($path === '/mypage' || preg_match('#^/(events|news)/\d+$#', $path)) {
            return $path;
        }

        return '/mypage';
    }
}
