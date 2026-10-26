<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->string('dodo_product_id')->nullable()->after('stripe_product_id');
            $table->string('paddle_price_id')->nullable()->after('dodo_product_id');
            $table->string('lemonsqueezy_variant_id')->nullable()->after('paddle_price_id');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['dodo_product_id', 'paddle_price_id', 'lemonsqueezy_variant_id']);
        });
    }
};
