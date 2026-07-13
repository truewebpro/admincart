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
        Schema::create('searchbrands', function (Blueprint $table) {
            $table->id('sbrand_id')->index();
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
        Schema::dropIfExists('searchbrands');
    }
};
