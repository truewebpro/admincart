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
        Schema::create('preferences', function (Blueprint $table) {
            $table->id('preference_id')->index();
            $table->string('home_title')->default('Home Page');
            $table->string('home_description',360)->default('Home Page Description');
            $table->string('home_image')->nullable();
            $table->string('shop_logo')->nullable();
            $table->json('social_links')->default(json_encode([]));
            $table->foreignId('shop_id')->constrained('shops', 'shop_id')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('preferences');
    }
};
