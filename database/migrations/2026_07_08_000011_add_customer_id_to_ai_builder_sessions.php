<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_builder_sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('ai_builder_sessions', 'customer_id')) {
                $table->unsignedBigInteger('customer_id')->nullable()->index()->after('admin_user_id');
                $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('ai_builder_sessions', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropColumn('customer_id');
        });
    }
};
