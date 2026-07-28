<?php

namespace App\Http\Controllers;

use App\Mail\PasswordReissuedMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PasswordReissueController extends Controller
{
    /**
     * パスワード再発行フォーム表示
     */
    public function showForm()
    {
        return view('auth.password-reissue');
    }

    /**
     * パスワード再発行処理
     */
    public function reissue(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'birth_date' => ['required', 'date', 'before:today'],
        ], [
            'email.required' => 'メールアドレスを入力してください。',
            'email.email' => 'メールアドレスの形式が正しくありません。',
            'birth_date.required' => '生年月日を入力してください。',
            'birth_date.date' => '生年月日の形式が正しくありません。',
            'birth_date.before' => '生年月日は今日より前の日付を指定してください。',
        ]);

        $email = mb_strtolower(trim($validated['email']));
        $birthDate = $validated['birth_date'];

        $user = User::whereRaw('LOWER(email) = ?', [$email])
            ->whereDate('birth_date', $birthDate)
            ->first();

        // アカウント有無の推測を防ぐため、常に同じ成功メッセージを返す
        $genericSuccessMessage = '入力情報が一致するアカウントがある場合は、再発行パスワードをメールで送信しました。';

        if (!$user) {
            Log::warning('パスワード再発行照合失敗', [
                'email_hash' => sha1($email),
                'birth_date' => $birthDate,
                'ip' => $request->ip(),
            ]);

            return redirect()->route('password.reissue.form')
                ->with('success', $genericSuccessMessage);
        }

        DB::beginTransaction();

        try {
            $newPassword = Str::password(12, true, true, true, false);

            $user->update([
                'password' => Hash::make($newPassword),
            ]);

            Mail::to($user->email)->send(new PasswordReissuedMail($user, $newPassword));

            DB::commit();

            Log::info('パスワード再発行成功', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('パスワード再発行失敗', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->withErrors(['error' => 'パスワード再発行メールの送信に失敗しました。時間をおいて再度お試しください。'])
                ->withInput();
        }

        return redirect()->route('password.reissue.form')
            ->with('success', $genericSuccessMessage);
    }
}
