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
        Schema::table('coupons', function (Blueprint $table) {
            $table->string('title',150)->nullable()->after('code');          // internal (admin)
            $table->string('display_title',150)->nullable()->after('title');  // frontend label
            $table->boolean('is_auto')->default(false)->after('display_title')->index();   // auto apply (bogo/bundle)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn(['title','display_title','is_auto']);
        });
    }
};
