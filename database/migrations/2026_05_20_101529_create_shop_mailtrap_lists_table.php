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
        Schema::create('shop_mailtrap_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops', 'shop_id')->cascadeOnDelete();
            $table->foreignId('mailtrap_account_id')->constrained('mailtrap_accounts')->cascadeOnDelete();
            $table->string('list_id');
            $table->string('list_name');
            $table->boolean('enabled')->default(true);
            $table->boolean('sync_customers')->default(true);
            $table->timestamps();

            $table->unique(['shop_id']);
            $table->index(['list_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_mailtrap_lists');
    }
};
