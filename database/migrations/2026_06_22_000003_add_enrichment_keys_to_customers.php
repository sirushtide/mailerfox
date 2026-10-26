<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->text('apollo_api_key')->nullable()->after('gemini_api_key');
            $table->text('hunter_api_key')->nullable()->after('apollo_api_key');
            $table->text('rocketreach_api_key')->nullable()->after('hunter_api_key');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['apollo_api_key', 'hunter_api_key', 'rocketreach_api_key']);
        });
    }
};
