<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::orderBy('group')->orderBy('key')->get()->groupBy('group');
        return view('admin.settings.index', compact('settings'));
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
        ];

        $validated = $request->validate($rules);

        // チェックボックス（未チェック時はリクエストに含まれない）
        $validated['registration_open'] = $request->boolean('registration_open') ? '1' : '0';

        Setting::setMany($validated);

        return redirect()->route('admin.settings.index')
            ->with('success', '設定を保存しました。');
    }
}
