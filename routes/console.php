<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Console\Scheduling\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// LINE プッシュ通知の月間カウントリセット（毎日午前0時に実行）
resolve(Schedule::class)->command('line:reset-monthly-count')->dailyAt('00:00');


