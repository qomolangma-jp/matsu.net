<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NewsController extends Controller
{
    private function redirectGuestToLiff(Request $request)
    {
        return redirect('/liff/bridge' . $request->getPathInfo());
    }

    /**
     * ニュース一覧（ログインユーザーの卒業年度でフィルタ）
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if ($user instanceof User) {
            $user = $user->fresh();
        }

        if (!$user) {
            return $this->redirectGuestToLiff($request);
        }

        $query = News::published()
            ->where(function ($q) use ($user) {
                $q->whereNull('target_graduation_years')
                  ->orWhereJsonContains('target_graduation_years', (string) $user->graduation_year);
            })
            ->orderByDesc('published_at');

        // キーワード検索
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                  ->orWhere('body', 'like', "%{$keyword}%");
            });
        }

        $news = $query->paginate(15)->withQueryString();

        return view('news.index', compact('news', 'user'));
    }

    /**
     * ニュース詳細
     */
    public function show(Request $request, News $news)
    {
        $user = Auth::user();

        if ($user instanceof User) {
            $user = $user->fresh();
        }

        if (!$user) {
            return $this->redirectGuestToLiff($request);
        }

        // 未公開は404
        if (!$news->published_at || $news->published_at->isFuture()) {
            abort(404);
        }

        // 対象卒業年度チェック
        if (!empty($news->target_graduation_years)
            && !in_array((string) $user->graduation_year, array_map('strval', $news->target_graduation_years))
        ) {
            abort(403, 'このお知らせは対象外です。');
        }

        return view('news.show', compact('news', 'user'));
    }
}
