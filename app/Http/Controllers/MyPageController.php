<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\News;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

class MyPageController extends Controller
{
    /**
     * マイページ（プロフィール表示）
     */
    public function index()
    {
        $user = Auth::user()?->fresh();

        if (!$user) {
            return redirect()->route('register.form');
        }

        // 公開済みニュース（自分の卒業年度対象 or 全体向け）を新しい順に5件
        $news = News::where('published_at', '<=', now())
            ->where(function ($q) use ($user) {
                $q->whereNull('target_graduation_years')
                  ->orWhereJsonContains('target_graduation_years', (string) $user->graduation_year);
            })
            ->orderByDesc('published_at')
            ->limit(5)
            ->get();

        // 公開済みイベント（自分の卒業年度対象 or 全体向け）を開催日順に5件
        $events = Event::where('is_published', true)
            ->where('event_date', '>=', now())
            ->where(function ($q) use ($user) {
                $q->whereNull('graduation_year')
                  ->orWhere('graduation_year', $user->graduation_year);
            })
            ->orderBy('event_date')
            ->limit(5)
            ->get();

        return view('mypage.index', compact('user', 'news', 'events'));
    }

    /**
     * プロフィール編集フォーム表示
     */
    public function edit()
    {
        $user = Auth::user()?->fresh();
        
        if (!$user) {
            return redirect()->route('register.form');
        }
        
        return view('mypage.edit', compact('user'));
    }

    /**
     * プロフィール更新処理
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('register.form');
        }

        $validated = $request->validate([
            'last_name' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'last_name_kana' => 'nullable|string|max:255',
            'first_name_kana' => 'nullable|string|max:255',
            'birth_date' => 'required|date',
            'email' => 'nullable|email|max:255',
            'postal_code' => 'nullable|string|max:10',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:20',
        ], [
            'last_name.required' => '姓は必須です。',
            'first_name.required' => '名は必須です。',
            'birth_date.required' => '生年月日は必須です。',
            'email.email' => 'メールアドレスの形式が正しくありません。',
        ]);

        DB::beginTransaction();
        
        try {
            $user->update($validated);
            
            DB::commit();
            
            Log::info('プロフィール更新', [
                'user_id' => $user->id,
                'updated_fields' => array_keys($validated),
            ]);
            
            return redirect()->route('mypage.index')
                ->with('success', 'プロフィールを更新しました。');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('プロフィール更新エラー', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            
            return redirect()->back()
                ->withErrors(['error' => 'プロフィールの更新に失敗しました。'])
                ->withInput();
        }
    }

    /**
     * パスワード変更フォーム表示（将来的な拡張用）
     */
    public function editPassword()
    {
        $user = Auth::user()?->fresh();
        
        if (!$user) {
            return redirect()->route('register.form');
        }
        
        return view('mypage.password', compact('user'));
    }

    /**
     * パスワード変更処理
     */
    public function updatePassword(Request $request)
    {
        $user = Auth::user()?->fresh();

        if (!$user) {
            return redirect()->route('register.form');
        }

        $request->validate([
            'current_password' => ['required'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.required' => '現在のパスワードを入力してください。',
            'password.required'         => '新しいパスワードを入力してください。',
            'password.min'              => 'パスワードは8文字以上で入力してください。',
            'password.confirmed'        => '新しいパスワードが一致しません。',
        ]);

        // 現在のパスワード確認
        if (!$user->password || !Hash::check($request->current_password, $user->password)) {
            return redirect()->back()
                ->withErrors(['current_password' => '現在のパスワードが正しくありません。'])
                ->withInput();
        }

        $user->update(['password' => Hash::make($request->password)]);

        return redirect()->route('mypage.password')
            ->with('success', 'パスワードを変更しました。');
    }
}
