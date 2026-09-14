<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_file_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_file_id')->constrained('media_files')->cascadeOnDelete();

            $table->string('attachable_type'); // e.g. 'App\Models\Product', 'App\Models\Blog'
            $table->unsignedBigInteger('attachable_id');

            // What this specific attachment is FOR — e.g. 'featured',
            // 'gallery', 'thumbnail'. Renamed from "role" to avoid
            // colliding with this codebase's existing use of "role"
            // for user permission levels (ShopUser.role).
            $table->string('media_for')->nullable();
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['attachable_type', 'attachable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_file_attachments');
    }
};
