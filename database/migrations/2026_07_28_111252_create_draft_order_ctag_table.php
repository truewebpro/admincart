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
        Schema::create('draft_order_ctag', function (Blueprint $table) {
            $table->foreignId('draft_order_id')->constrained('draft_orders')->cascadeOnDelete();
            $table->foreignId('ctag_id')->constrained('ctags')->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['draft_order_id', 'ctag_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('draft_order_ctags');
    }
};
