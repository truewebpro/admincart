<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops', 'shop_id')->cascadeOnDelete();

            // e.g. 'collection_seo_sync', 'product_seo_sync',
            // 'variant_cost_sync', 'variant_backfill', 'blog_seo_sync'
            $table->string('job_type');

            $table->enum('status', ['queued', 'processing', 'completed', 'failed'])->default('queued');

            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();

            // Flexible per-job result data — e.g. {"updated": 45, "skipped": 2}
            $table->json('result_summary')->nullable();
            $table->text('error_message')->nullable();

            $table->timestamps();

            // The core query this table exists to answer: "is a job of
            // this type currently running for this shop" — also
            // replaces the old Cache-based lock entirely.
            $table->index(['shop_id', 'job_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_runs');
    }
};
