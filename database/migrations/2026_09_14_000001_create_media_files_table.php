<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops', 'shop_id')->cascadeOnDelete();

            $table->string('thirdparty_id')->nullable();   // null = manually uploaded, never touched Shopify
            $table->string('thirdparty_url')->nullable();   // original Shopify CDN url — dedup key on re-sync
            $table->enum('file_type', ['image', 'generic'])->default('image');

            $table->string('filename')->nullable();
            $table->string('path');                          // your S3 path — the actual source of truth
            $table->string('alt_text')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();

            $table->timestamps();

            $table->index(['shop_id', 'thirdparty_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_files');
    }
};
