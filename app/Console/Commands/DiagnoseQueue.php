<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DiagnoseQueue extends Command
{
    protected $signature = 'queue:diagnose {shop_id} {--clear-lock : Clear the stuck "sync running" cache flag for this shop}';
    protected $description = 'Diagnose queue/sync status for a shop — jobs table, queued jobs, cache lock, and recent failures';

    public function handle(): int
    {
        $shopId = $this->argument('shop_id');

        $this->info("=== Queue diagnostics for shop_id {$shopId} ===");
        $this->newLine();

        // 1. Does the jobs table exist at all?
        $hasJobsTable = Schema::hasTable('jobs');
        $this->line('jobs table exists: ' . ($hasJobsTable ? '<fg=green>yes</>' : '<fg=red>NO — run php artisan queue:table && php artisan migrate</>'));

        // 2. How many jobs are currently queued (waiting to be processed)?
        if ($hasJobsTable) {
            $queuedCount = DB::table('jobs')->count();
            $this->line("jobs currently queued: {$queuedCount}");

            if ($queuedCount > 0) {
                $oldest = DB::table('jobs')->orderBy('created_at')->first();
                $this->line('oldest queued job created_at: ' . ($oldest->created_at ?? 'unknown'));
            }
        }

        // 3. Is the "sync running" cache flag currently set for this shop?
        $lockKey = "collection_seo_sync_running_{$shopId}";
        $lockSet = Cache::has($lockKey);
        $this->line('sync-running cache flag set: ' . ($lockSet ? '<fg=yellow>yes</>' : '<fg=green>no</>'));

        if ($lockSet && $this->option('clear-lock')) {
            Cache::forget($lockKey);
            $this->info('Cleared the stuck cache flag.');
        } elseif ($lockSet) {
            $this->comment('Run again with --clear-lock to clear this flag.');
        }

        // 4. Recent failed jobs, if any.
        if (Schema::hasTable('failed_jobs')) {
            $recentFailures = DB::table('failed_jobs')
                ->orderByDesc('failed_at')
                ->limit(3)
                ->get(['uuid', 'failed_at', 'exception']);

            $this->newLine();
            $this->line('Recent failed jobs: ' . $recentFailures->count());

            foreach ($recentFailures as $failure) {
                $this->line("  - {$failure->failed_at}: " . mb_substr($failure->exception, 0, 200) . '...');
            }
        } else {
            $this->line('failed_jobs table does not exist (no failure history available).');
        }

        // 5. What's the actual configured queue connection right now?
        $this->newLine();
        $this->line('QUEUE_CONNECTION: ' . config('queue.default'));

        return self::SUCCESS;
    }
}
