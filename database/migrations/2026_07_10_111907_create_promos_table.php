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
        Schema::create('promos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')
                ->constrained('shops', 'shop_id')
                ->cascadeOnDelete();

            $table->enum('page_type', [
                'homepage',
                'products',
                'cats',
                'brands',
                'blogs',
                'pages',
                'policies'
            ]);

            $table->string('heading')->nullable();
            $table->string('subheading')->nullable();

            $table->string('style')->default('style1');

            $table->string('bg_color',20)->default('#ffffff');
            $table->string('title_color',20)->default('#000000');
            $table->string('subtext_color',20)->default('#666666');

            $table->boolean('status')->default(true);
            $table->enum('position',['top','bottom'])->default('bottom');

            $table->timestamps();

            $table->unique(['shop_id', 'page_type', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promos');
    }
};
