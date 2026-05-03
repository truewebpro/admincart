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
        Schema::create('sumups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops','shop_id')->cascadeOnDelete()->unique();
            $table->string('client_id');
            $table->text('client_secret');
            $table->string('merchant_email');
            $table->enum('mode', ['sandbox', 'live'])->default('live');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sumups');
    }
};
