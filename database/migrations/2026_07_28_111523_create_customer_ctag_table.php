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
        Schema::create('customer_ctag', function (Blueprint $table) {
            $table->foreignId('customer_id')->constrained('customers','customer_id')->cascadeOnDelete();
            $table->foreignId('ctag_id')->constrained('ctags')->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['customer_id', 'ctag_id']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_ctags');
    }
};
