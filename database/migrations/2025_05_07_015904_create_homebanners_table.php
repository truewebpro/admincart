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
        Schema::create('homebanners', function (Blueprint $table) {
            $table->id('banner_id')->index();
            $table->string('heading')->default('Home Banner');
            $table->string('subheading')->nullable();
            $table->string('button_text')->nullable();
            $table->string('link')->nullable();
            $table->enum('status',['Active','Inactive'])->default('Active');
            $table->string('image_url')->nullable();
            $table->foreignId('shop_id')->constrained('shops','shop_id')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('homebanners');
    }
};
