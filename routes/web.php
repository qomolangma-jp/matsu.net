<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\MyPageController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\NewsController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\ReferenceRosterController;
use App\Http\Controllers\Admin\CategoryController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// トップページ
Route::get('/', function () {
    return redirect()->route('register.form');
});

// ログイン画面（デフォルトリダイレクト用）
Route::get('/login', function () {
    return redirect()->route('test.login.form');
})->name('login');

// 新規登録画面
Route::get('/register', [RegisterController::class, 'showForm'])->name('register.form');

// 新規登録処理（LIFF経由）
Route::post('/register', [RegisterController::class, 'store'])->name('register.submit');

// 登録完了画面
Route::get('/register/complete', function () {
    return view('register-complete');
})->name('register.complete');

// テストログイン（開発環境専用）
Route::get('/test-login', function () {
    return view('test-login');
})->name('test.login.form');

// 自動ログインテスト（開発環境専用）
Route::get('/test-auto-login', function () {
    if (config('app.env') !== 'local') {
        abort(404);
    }
    return view('test-auto-login');
})->name('test.auto.login');

Route::post('/test-login', function (\Illuminate\Http\Request $request) {
    $user = \App\Models\User::where('email', $request->email)->first();
    
    if (!$user) {
        return back()->with('error', 'ユーザーが見つかりません');
    }
    
    if (!in_array($user->role, ['master_admin', 'year_admin'])) {
        return back()->with('error', '管理者権限がありません');
    }
    
    Auth::login($user);
    
    return redirect()->route('admin.users.index');
})->name('test.login');

Route::post('/logout', function () {
    Auth::logout();
    return redirect()->route('test.login.form');
})->name('logout');

// マイページ（全ユーザーアクセス可）
Route::middleware(['auth'])->prefix('mypage')->name('mypage.')->group(function () {
    Route::get('/', [MyPageController::class, 'index'])->name('index');
    Route::get('/edit', [MyPageController::class, 'edit'])->name('edit');
    Route::put('/update', [MyPageController::class, 'update'])->name('update');
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
        Route::get('/', [NewsController::class, 'index'])->name('index');
        Route::get('/create', [NewsController::class, 'create'])->name('create');
        Route::post('/', [NewsController::class, 'store'])->name('store');
        Route::get('/{news}/edit', [NewsController::class, 'edit'])->name('edit');
        Route::put('/{news}', [NewsController::class, 'update'])->name('update');
        Route::delete('/{news}', [NewsController::class, 'destroy'])->name('destroy');
    });

    // イベント管理
    Route::prefix('events')->name('events.')->group(function () {
        Route::get('/', [EventController::class, 'index'])->name('index');
        Route::get('/create', [EventController::class, 'create'])->name('create');
        Route::post('/', [EventController::class, 'store'])->name('store');
        Route::get('/{event}', [EventController::class, 'show'])->name('show');
        Route::get('/{event}/edit', [EventController::class, 'edit'])->name('edit');
        Route::put('/{event}', [EventController::class, 'update'])->name('update');
        Route::delete('/{event}', [EventController::class, 'destroy'])->name('destroy');
        Route::get('/{event}/export-attendances', [EventController::class, 'exportAttendances'])->name('export-attendances');
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
});
