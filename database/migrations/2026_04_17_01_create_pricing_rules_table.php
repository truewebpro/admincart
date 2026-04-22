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
        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')
                ->constrained('shops','shop_id')
                ->onDelete('cascade');
            $table->string('name');
            $table->enum('type', ['bundle', 'volume']);
            $table->integer('min_qty'); // e.g. 4 items
            $table->decimal('price', 10, 2)->nullable(); // fixed bundle price
            $table->decimal('discount_percent', 5, 2)->nullable();
            $table->enum('scope', ['all', 'products', 'cats'])->default('all');
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->index('shop_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pricing_rules');
    }
};
