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
        Schema::create('businesses', function (Blueprint $table) {
            $table->id('business_id')->index();
            $table->string('business_name');
            $table->string('address_line1');
            $table->string('address_line2');
            $table->string('region');
            $table->string('postcode');
            $table->string('country')->default('GB');
            $table->string('reg_no')->nullable();
            $table->string('vat_no')->nullable();
            $table->string('email');
            $table->string('phone');
            $table->string('whatsapp')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};
