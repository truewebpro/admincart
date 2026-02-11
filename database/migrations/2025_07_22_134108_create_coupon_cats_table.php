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
        Schema::create('coupon_cats', function (Blueprint $table) {
            $table->id('coupon_cat_id')->index();
            $table->foreignId('coupon_id')->constrained('coupons','coupon_id')->onDelete('cascade');
            $table->foreignId('cat_id')->constrained('cats','cat_id')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupon_cats');
    }
};
