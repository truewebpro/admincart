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
        Schema::create('stypes', function (Blueprint $table) {
            $table->id('stype_id')->index();
            $table->string('stype_name'); // e.g. Hero Banner, Image with Text, Testimonials
            $table->string('stype_slug')->unique(); // e.g. hero-banner, image-with-text
            $table->json('stype_json')->nullable(); // default JSON structure for content
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stypes');
    }
};
