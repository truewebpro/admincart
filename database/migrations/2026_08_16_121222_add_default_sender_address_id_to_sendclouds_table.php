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
        Schema::table('sendclouds', function (Blueprint $table) {
            $table->unsignedBigInteger('default_sender_address_id')->nullable()->after('secret_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sendclouds', function (Blueprint $table) {
            $table->dropColumn('default_sender_address_id');
        });
    }
};
