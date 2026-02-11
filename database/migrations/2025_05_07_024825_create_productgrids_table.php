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
        Schema::create('productgrids', function (Blueprint $table) {
            $table->id('pgrid_id')->index();
            $table->enum('pgrid_name',['topseller','latestpro','recommended'])->default('topseller');
            $table->string('heading')->default('Top Sellers');
            $table->string('subheading')->nullable();
            $table->integer('limit')->default(12);
            $table->enum('status',['Active','Inactive'])->default('Active');
            $table->foreignId('cat_id')->constrained('cats','cat_id')->cascadeOnDelete();
            $table->foreignId('shop_id')->constrained('shops','shop_id')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productgrids');
    }
};
