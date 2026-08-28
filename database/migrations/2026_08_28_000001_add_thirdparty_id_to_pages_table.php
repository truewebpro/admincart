<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->string('thirdparty_id')->nullable()->after('shop_id');
            $table->unique(['shop_id', 'thirdparty_id']);
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropUnique(['shop_id', 'thirdparty_id']);
            $table->dropColumn('thirdparty_id');
        });
    }
};
