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
        Schema::create('pricing_rule_cats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pricing_rule_id')
                ->constrained('pricing_rules')
                ->onDelete('cascade');

            $table->foreignId('cat_id')
                ->constrained('cats', 'cat_id')
                ->onDelete('cascade');

            $table->timestamps();

            $table->unique(['pricing_rule_id', 'cat_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pricing_rule_cats');
    }
};
