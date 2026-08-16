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
            $table->string('api_version')->default('v3')->after('secret_key'); // 'v2' or 'v3'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sendclouds', function (Blueprint $table) {
            $table->dropColumn('api_version');
        });
    }
};
