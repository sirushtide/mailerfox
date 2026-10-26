<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected array $tables = [
        'email_lists',
        'campaigns',
        'automations',
        'auto_responders',
        'delivery_servers',
        'bounce_servers',
        'tracking_domains',
        'sending_domains',
        'email_warmups',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'workspace_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->unsignedBigInteger('workspace_id')->nullable()->after('customer_id');
                    $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
                    $table->index('workspace_id');
                });
            }
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->tables) as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'workspace_id')) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    $table->dropForeign([$tableName . '_workspace_id_foreign']);
                    $table->dropIndex([$tableName . '_workspace_id_index']);
                    $table->dropColumn('workspace_id');
                });
            }
        }
    }
};
