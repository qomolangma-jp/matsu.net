<?php

namespace App\Console\Commands;

use App\Services\ReferenceRosterSyncService;
use Illuminate\Console\Command;

class SyncReferenceRosterUsers extends Command
{
    protected $signature = 'reference-rosters:sync-users';

    protected $description = '参照名簿に登録済みの既存ユーザーを照合し、承認・権限・登録済みフラグを同期する';

    public function handle(): int
    {
        $this->info('参照名簿同期を開始します...');

        $result = app(ReferenceRosterSyncService::class)->syncExistingUsers();

        $this->info("同期完了: {$result['processed']}名を更新しました。承認済み: {$result['approved']}名、学年管理者付与: {$result['granted_year_admin']}名");

        return self::SUCCESS;
    }
}
