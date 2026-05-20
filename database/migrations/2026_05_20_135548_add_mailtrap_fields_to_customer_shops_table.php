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
        Schema::table('customer_shops', function (Blueprint $table) {
            $table->string('mailtrap_contact_id')->nullable()->after('status');
            $table->boolean('mailtrap_synced')->default(false)->after('mailtrap_contact_id');
            $table->timestamp('mailtrap_synced_at')->nullable()->after('mailtrap_synced');
            $table->text('mailtrap_last_error')->nullable()->after('mailtrap_synced_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_shops', function (Blueprint $table) {
            $table->dropColumn([
                'mailtrap_contact_id',
                'mailtrap_synced',
                'mailtrap_synced_at',
                'mailtrap_last_error',
            ]);
        });
    }
};
