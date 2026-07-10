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
        Schema::create('home_promo_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('home_promo_id')
                ->constrained('home_promos')
                ->cascadeOnDelete();

            $table->enum('media_type', ['icon', 'svg', 'image'])->default('icon');
            $table->longText('media_value');

            $table->string('title');
            $table->text('subtext')->nullable();

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('home_promo_items');
    }
};
