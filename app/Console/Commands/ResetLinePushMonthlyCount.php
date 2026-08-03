<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Setting;
use Illuminate\Support\Carbon;

class ResetLinePushMonthlyCount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'line:reset-monthly-count';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'LINE プッシュ通知の月間送信カウントをリセット（月1日に実行推奨）';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $currentMonth = Carbon::now()->format('Y-m');
        $previousMonth = Setting::get('line_push_current_month');

        // 月が異なる場合にリセット
        if ($previousMonth !== $currentMonth) {
            Setting::set('line_push_current_month', $currentMonth);

            $this->info('LINE プッシュ通知の月間カウントをリセットしました');
            $this->info("前月: {$previousMonth}");
            $this->info("今月: {$currentMonth}");

            return Command::SUCCESS;
        }

        $this->info('月間カウントのリセットは不要です（既に今月のデータです）');

        return Command::SUCCESS;
    }
}
