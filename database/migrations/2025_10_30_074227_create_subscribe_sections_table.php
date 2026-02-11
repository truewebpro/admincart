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
        Schema::create('subscribe_sections', function (Blueprint $table) {
            $table->id();
            $table->enum('style',['style1','style2','style3'])->default('style1');
            $table->string('headline')->default('Subscribe to our newsletter');
            $table->string('subheadline')->default('Start and grow your business');
            $table->string('promo_text')->default('Be the first to hear about new products, fantastic special offers, and news.');
            $table->string('privacy_text')->default('We value your privacy and promise to keep your details safe.');
            $table->json('settings')->default(json_encode([
                'background_color' => '#212529',
                'text_color' => '#ffffff'
            ]));
            $table->json('extras')->default(json_encode([
                [
                    'icon' => 'mdi-envelope',
                    'title' => 'New Arrivals',
                    'detail' => 'Get updates on the latest products & innovations.'
                ],
                [
                    'icon' => 'mdi-calendar',
                    'title' => 'Sent weekly',
                    'detail' => 'We send weekly emails, directly to your inbox.'
                ],
                [
                    'icon' => 'mdi-lock',
                    'title' => 'Safe & secure',
                    'detail' => 'We respect your privacy, so we’ll keep your details safe.'
                ]
            ]));
            $table->foreignId('shop_id')->constrained('shops','shop_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscribe_sections');
    }
};
