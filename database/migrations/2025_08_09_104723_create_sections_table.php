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
        Schema::create('sections', function (Blueprint $table) {
            $table->id('section_id')->index();
            $table->foreignId('stype_id')->constrained('stypes','stype_id')->onDelete('cascade'); // Section type
            $table->morphs('sectionable');// creates sectionable_id & sectionable_type
            $table->json('section_json')->nullable(); // store actual section content
            $table->integer('sort_order')->default(0); // sort order
            $table->enum('section_status', ['show', 'hide'])->default('show');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};
