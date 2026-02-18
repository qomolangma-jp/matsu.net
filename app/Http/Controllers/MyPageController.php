<?php

namespace App\Http\Controllers;

use App\Models\User;
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
        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('register.form');
        }
        
        return view('mypage.index', compact('user'));
    }

    /**
     * プロフィール編集フォーム表示
     */
    public function edit()
    {
        $user = Auth::user();
        
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
        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('register.form');
        }
        
        return view('mypage.password', compact('user'));
    }
}
