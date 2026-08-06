<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('page_views', function (Blueprint $table) {
            $table->string('country', 100)->nullable()->after('browser');
            $table->string('region', 100)->nullable()->after('country');
            $table->string('city', 100)->nullable()->after('region');
            $table->decimal('latitude', 9, 6)->nullable()->after('city');
            $table->decimal('longitude', 9, 6)->nullable()->after('latitude');
        });

        Schema::table('active_visitors', function (Blueprint $table) {
            $table->string('country', 100)->nullable()->after('current_path');
            $table->string('region', 100)->nullable()->after('country');
            $table->string('city', 100)->nullable()->after('region');
            $table->decimal('latitude', 9, 6)->nullable()->after('city');
            $table->decimal('longitude', 9, 6)->nullable()->after('latitude');

            $table->index(['shop_id', 'country']); // powers "Sessions by location"
        });
    }

    public function down(): void
    {
        Schema::table('page_views', function (Blueprint $table) {
            $table->dropColumn(['country', 'region', 'city', 'latitude', 'longitude']);
        });

        Schema::table('active_visitors', function (Blueprint $table) {
            $table->dropIndex(['shop_id', 'country']);
            $table->dropColumn(['country', 'region', 'city', 'latitude', 'longitude']);
        });
    }
};
