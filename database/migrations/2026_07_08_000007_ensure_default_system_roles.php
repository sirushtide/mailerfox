<?php

use App\Services\RoleService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(RoleService::class)->ensureDefaultRoles();
    }

    public function down(): void
    {
        // No-op: default roles are intentionally preserved.
    }
};
