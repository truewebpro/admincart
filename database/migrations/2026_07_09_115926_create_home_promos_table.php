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
        Schema::create('home_promos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('homepage_id')
                ->constrained('homepages','homepage_id')
                ->cascadeOnDelete();
            $table->foreignId('shop_id')->constrained('shops','shop_id')->cascadeOnDelete();
            $table->enum('position', ['top', 'bottom'])->default('top');
            $table->string('heading')->nullable();
            $table->string('subheading')->nullable();

            $table->string('style')->default('style1');

            $table->string('bg_color', 20)->default('#ffffff');
            $table->string('title_color', 20)->default('#000000');
            $table->string('subtext_color', 20)->default('#666666');

            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->unique(['shop_id', 'homepage_id', 'position']);
            $table->index(['shop_id', 'homepage_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('home_promos');
    }
};
