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
        Schema::create('footers', function (Blueprint $table) {
            $table->id('footer_id')->index();
            $table->enum('style',['style1','style2','style3'])->default('style3');
            $table->json('fsections')->nullable();
            $table->json('settings')->nullable();
            $table->foreignId('shop_id')->constrained('shops','shop_id')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('footers');
    }
};
