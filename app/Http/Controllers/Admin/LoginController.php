<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function showLoginForm(Request $request)
    {
        if (Auth::check() && in_array(Auth::user()->role, ['master_admin', 'year_admin'])) {
            return redirect()->route('admin.users.index');
        }

        if (!$this->isMobileBrowser($request->userAgent() ?? '')) {
            return view('auth.line-login');
        }

        return view('admin.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = \App\Models\User::where('email', $request->email)
            ->whereIn('role', ['master_admin', 'year_admin'])
            ->first();

        if (!$user || !password_verify($request->password, $user->password ?? '')) {
            throw ValidationException::withMessages([
                'email' => 'メールアドレスまたはパスワードが正しくありません。',
            ]);
        }

        Auth::login($user, $request->boolean('remember'));

        $request->session()->regenerate();

        return redirect()->intended(route('admin.users.index'));
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login.form');
    }

    private function isMobileBrowser(string $userAgent): bool
    {
        return (bool) preg_match('/iPhone|iPod|iPad|Android.*Mobile|Windows Phone|webOS|BlackBerry|Opera Mini/i', $userAgent);
    }
}
