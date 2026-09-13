<?php

namespace App\Jobs\Concerns;

use App\Models\JobRun;

/**
 * Used by every sync/backfill job. Replaces the old per-job
 * Cache::has()/put()/forget() lock pattern with a persistent,
 * queryable history — so your dashboard can show "last run: 2 min
 * ago, 45 updated" instead of just a transient yes/no flag that
 * disappears once cleared.
 *
 * Usage inside a job's handle():
 *
 *   $run = $this->startRun($this->shopId, 'collection_seo_sync');
 *   try {
 *       // ... do the work ...
 *       $this->completeRun($run, ['updated' => $updated]);
 *   } catch (\Throwable $e) {
 *       $this->failRun($run, $e->getMessage());
 *       throw $e;
 *   }
 */
trait TracksJobRun
{
    protected function isAlreadyRunning(int $shopId, string $jobType): bool
    {
        return JobRun::where('shop_id', $shopId)
            ->where('job_type', $jobType)
            ->where('status', 'processing')
            ->exists();
    }

    protected function startRun(int $shopId, string $jobType): JobRun
    {
        return JobRun::create([
            'shop_id'     => $shopId,
            'job_type'    => $jobType,
            'status'      => 'processing',
            'started_at'  => now(),
        ]);
    }

    protected function completeRun(JobRun $run, array $resultSummary = []): void
    {
        $run->update([
            'status'          => 'completed',
            'finished_at'     => now(),
            'result_summary'  => $resultSummary,
        ]);
    }

    protected function failRun(JobRun $run, string $errorMessage): void
    {
        $run->update([
            'status'         => 'failed',
            'finished_at'    => now(),
            'error_message'  => \Illuminate\Support\Str::limit($errorMessage, 2000),
        ]);
    }
}
