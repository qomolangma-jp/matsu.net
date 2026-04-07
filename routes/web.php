<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\MyPageController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\NewsController as AdminNewsController;
use App\Http\Controllers\Admin\EventController as AdminEventController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\Admin\ReferenceRosterController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\SettingController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// トップページ
Route::get('/', function () {
    // LINEログイン後のリダイレクト先（?liff.state=...&code=... が付いている場合）
    // LIFF SDKが認証コードを処理するために、ここで liff.init() を実行してから SDK に /register へ誘導させる
    if (request()->has('liff.state')) {
        $liffId = config('services.line.liff_id');
        return response(view('liff-redirect', ['liffId' => $liffId]));
    }
    return redirect()->route('register.form');
});

// 管理者ログイン
Route::get('/admin', function () {
    return redirect()->route('admin.login.form');
});
Route::get('/admin/login', [LoginController::class, 'showLoginForm'])->name('admin.login.form');
Route::post('/admin/login', [LoginController::class, 'login'])->name('admin.login');
Route::post('/admin/logout', [LoginController::class, 'logout'])->name('admin.logout');

// ログイン画面（デフォルトリダイレクト用）
Route::get('/login', function () {
    return redirect()->route('admin.login.form');
})->name('login');

// 新規登録画面
Route::get('/register', [RegisterController::class, 'showForm'])->name('register.form');

// 新規登録処理（LIFF経由）
Route::post('/register', [RegisterController::class, 'store'])->name('register.submit');

// 登録完了画面
Route::get('/register/complete', function () {
    return view('register-complete');
})->name('register.complete');

// 自動ログインテスト（開発環境専用）
Route::get('/test-auto-login', function () {
    if (config('app.env') !== 'local') {
        abort(404);
    }
    return view('test-auto-login');
})->name('test.auto.login');

Route::post('/logout', function (\Illuminate\Http\Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect()->route('admin.login.form');
})->name('logout');

// マイページ（全ユーザーアクセス可）
Route::middleware(['auth'])->prefix('mypage')->name('mypage.')->group(function () {
    Route::get('/', [MyPageController::class, 'index'])->name('index');
    Route::get('/edit', [MyPageController::class, 'edit'])->name('edit');
    Route::put('/update', [MyPageController::class, 'update'])->name('update');
    Route::get('/password', [MyPageController::class, 'editPassword'])->name('password');
    Route::put('/password', [MyPageController::class, 'updatePassword'])->name('password.update');
});

// お知らせ（ログインユーザー向け）
Route::middleware(['auth'])->prefix('news')->name('news.')->group(function () {
    Route::get('/', [NewsController::class, 'index'])->name('index');
    Route::get('/{news}', [NewsController::class, 'show'])->name('show');
});

// イベント（ログインユーザー向け）
Route::middleware(['auth'])->prefix('events')->name('events.')->group(function () {
    Route::get('/', [EventController::class, 'index'])->name('index');
    Route::get('/{event}', [EventController::class, 'show'])->name('show');
    Route::post('/{event}/respond', [EventController::class, 'respond'])->name('respond');
});

// 管理者用ルート
Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {
    // 名簿管理
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserManagementController::class, 'index'])->name('index');
        Route::get('/export', [UserManagementController::class, 'export'])->name('export');
        Route::get('/{user}/edit', [UserManagementController::class, 'edit'])->name('edit');
        Route::put('/{user}', [UserManagementController::class, 'update'])->name('update');
        Route::post('/{user}/approve', [UserManagementController::class, 'approve'])->name('approve');
        Route::post('/{user}/reject', [UserManagementController::class, 'reject'])->name('reject');
    });

    // ニュース管理
    Route::prefix('news')->name('news.')->group(function () {
        Route::get('/', [AdminNewsController::class, 'index'])->name('index');
        Route::get('/create', [AdminNewsController::class, 'create'])->name('create');
        Route::post('/', [AdminNewsController::class, 'store'])->name('store');
        Route::get('/{news}/edit', [AdminNewsController::class, 'edit'])->name('edit');
        Route::put('/{news}', [AdminNewsController::class, 'update'])->name('update');
        Route::delete('/{news}', [AdminNewsController::class, 'destroy'])->name('destroy');
    });

    // イベント管理
    Route::prefix('events')->name('events.')->group(function () {
        Route::get('/', [AdminEventController::class, 'index'])->name('index');
        Route::get('/create', [AdminEventController::class, 'create'])->name('create');
        Route::post('/', [AdminEventController::class, 'store'])->name('store');
        Route::get('/{event}', [AdminEventController::class, 'show'])->name('show');
        Route::get('/{event}/edit', [AdminEventController::class, 'edit'])->name('edit');
        Route::put('/{event}', [AdminEventController::class, 'update'])->name('update');
        Route::delete('/{event}', [AdminEventController::class, 'destroy'])->name('destroy');
        Route::get('/{event}/export-attendances', [AdminEventController::class, 'exportAttendances'])->name('export-attendances');
    });

    // 参照名簿管理
    Route::prefix('reference-rosters')->name('reference_rosters.')->group(function () {
        Route::get('/', [ReferenceRosterController::class, 'index'])->name('index');
        Route::post('/import', [ReferenceRosterController::class, 'import'])->name('import');
        Route::get('/export', [ReferenceRosterController::class, 'export'])->name('export');
    });

    // カテゴリー管理（マスター管理者のみ）
    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('index');
        Route::get('/create', [CategoryController::class, 'create'])->name('create');
        Route::post('/', [CategoryController::class, 'store'])->name('store');
        Route::get('/{category}/edit', [CategoryController::class, 'edit'])->name('edit');
        Route::put('/{category}', [CategoryController::class, 'update'])->name('update');
        Route::delete('/{category}', [CategoryController::class, 'destroy'])->name('destroy');
    });

    // システム設定（マスター管理者のみ）
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
});
