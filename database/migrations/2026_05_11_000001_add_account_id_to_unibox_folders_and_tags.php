<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('unibox_folders') && !Schema::hasColumn('unibox_folders', 'account_id')) {
            Schema::table('unibox_folders', function (Blueprint $table) {
                $table->unsignedBigInteger('account_id')->nullable()->after('customer_id');
                $table->index(['customer_id', 'account_id']);
                $table->foreign('account_id')->references('id')->on('unibox_accounts')->onDelete('cascade');
            });
        }

        if (Schema::hasTable('unibox_tags') && !Schema::hasColumn('unibox_tags', 'account_id')) {
            // Drop the old unique(customer_id, name) so we can widen it to include account_id.
            try {
                Schema::table('unibox_tags', function (Blueprint $table) {
                    $table->dropUnique('unibox_tags_customer_id_name_unique');
                });
            } catch (\Throwable $e) {
                // Index name may differ across databases; fall back to columns-based drop.
                try {
                    Schema::table('unibox_tags', function (Blueprint $table) {
                        $table->dropUnique(['customer_id', 'name']);
                    });
                } catch (\Throwable $e2) {
                    // ignore — if it cannot be dropped we'll still add account_id below.
                }
            }

            Schema::table('unibox_tags', function (Blueprint $table) {
                $table->unsignedBigInteger('account_id')->nullable()->after('customer_id');
                $table->index(['customer_id', 'account_id']);
                $table->foreign('account_id')->references('id')->on('unibox_accounts')->onDelete('cascade');
                $table->unique(['customer_id', 'account_id', 'name'], 'unibox_tags_customer_account_name_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('unibox_tags') && Schema::hasColumn('unibox_tags', 'account_id')) {
            Schema::table('unibox_tags', function (Blueprint $table) {
                try { $table->dropUnique('unibox_tags_customer_account_name_unique'); } catch (\Throwable $e) {}
                try { $table->dropForeign(['account_id']); } catch (\Throwable $e) {}
                try { $table->dropIndex(['customer_id', 'account_id']); } catch (\Throwable $e) {}
                $table->dropColumn('account_id');
                $table->unique(['customer_id', 'name']);
            });
        }

        if (Schema::hasTable('unibox_folders') && Schema::hasColumn('unibox_folders', 'account_id')) {
            Schema::table('unibox_folders', function (Blueprint $table) {
                try { $table->dropForeign(['account_id']); } catch (\Throwable $e) {}
                try { $table->dropIndex(['customer_id', 'account_id']); } catch (\Throwable $e) {}
                $table->dropColumn('account_id');
            });
        }
    }
};
