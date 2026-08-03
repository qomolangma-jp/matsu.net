<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\LineNotificationLog;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::orderBy('group')->orderBy('key')->get()->groupBy('group');
        
        // LINE 通知の残り送信可能数を計算
        $linePushRemaining = LineNotificationLog::getRemainingCount();
        $linePushSent = LineNotificationLog::getThisMonthCount();
        $linePushLimit = (int) Setting::get('line_push_limit', 200);

        return view('admin.settings.index', compact(
            'settings',
            'linePushRemaining',
            'linePushSent',
            'linePushLimit'
        ));
    }

    public function update(Request $request)
    {
        $rules = [
            'site_name'                   => ['required', 'string', 'max:100'],
            'association_name'            => ['required', 'string', 'max:100'],
            'admin_email'                 => ['nullable', 'email', 'max:255'],
            'registration_open'           => ['nullable', 'boolean'],
            'registration_closed_message' => ['nullable', 'string', 'max:1000'],
            'line_channel_access_token'   => ['nullable', 'string', 'max:500'],
            'liff_id'                     => ['nullable', 'string', 'max:100'],
            'line_push_limit'             => ['required', 'integer', 'min:1', 'max:10000'],
        ];

        $validated = $request->validate($rules);

        // チェックボックス（未チェック時はリクエストに含まれない）
        $validated['registration_open'] = $request->boolean('registration_open') ? '1' : '0';

        Setting::setMany($validated);

        return redirect()->route('admin.settings.index')
            ->with('success', '設定を保存しました。');
    }
}
