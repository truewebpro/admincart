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
        Schema::create('rules', function (Blueprint $table) {
            $table->enum('column',['title','type','vendor','tag','price','stock']);
            $table->enum('relation',['equals','not_equals','contains','not_contains','greater_than','less_than']);
            $table->string('condition');
            $table->timestamps();
            $table->foreignId('cat_id')->constrained('cats','cat_id')->cascadeOnDelete();
            $table->foreignId('shop_id')->constrained('shops','shop_id')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rules');
    }
};
