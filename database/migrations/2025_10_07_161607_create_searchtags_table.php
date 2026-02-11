<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('searchtags', function (Blueprint $table) {
            $table->id('stag_id')->index();
            $table->string('title');
            $table->string('link');
            $table->enum('status',['active','inactive'])->default('active');
            $table->foreignId('shop_id')->constrained('shops','shop_id')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('searchtags');
    }
};
