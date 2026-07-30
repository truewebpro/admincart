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
        Schema::create('shop_number_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops','shop_id')->cascadeOnDelete();
            $table->string('sequence_type', 50); // e.g. 'draft_order', 'order', 'invoice'
            $table->unsignedBigInteger('last_number')->default(0);
            $table->timestamps();

            $table->unique(['shop_id', 'sequence_type']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_number_sequences');
    }
};
