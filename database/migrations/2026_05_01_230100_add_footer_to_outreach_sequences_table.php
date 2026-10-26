<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outreach_sequences', function (Blueprint $table) {
            $table->text('footer_a')->nullable()->after('body_a');
            $table->text('footer_b')->nullable()->after('body_b');
        });
    }

    public function down(): void
    {
        Schema::table('outreach_sequences', function (Blueprint $table) {
            $table->dropColumn(['footer_a', 'footer_b']);
        });
    }
};
