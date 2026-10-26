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
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            Schema::create('site_layouts_new', function (Blueprint $table) {
                $table->id();
                $table->enum('type', ['header', 'footer', 'megamenu'])->default('header');
                $table->string('name');
                $table->json('display_conditions')->nullable();
                $table->json('builder_data')->nullable();
                $table->longText('html_content')->nullable();
                $table->enum('status', ['draft', 'publish'])->default('draft');
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index('type');
                $table->index('status');
                $table->index(['type', 'status']);
            });

            $connection->statement('INSERT INTO site_layouts_new SELECT * FROM site_layouts');
            Schema::drop('site_layouts');
            Schema::rename('site_layouts_new', 'site_layouts');
        } else {
            Schema::table('site_layouts', function (Blueprint $table) {
                $table->enum('type', ['header', 'footer', 'megamenu'])->default('header')->change();
            });
        }
    }

    public function down(): void
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            Schema::create('site_layouts_new', function (Blueprint $table) {
                $table->id();
                $table->enum('type', ['header', 'footer'])->default('header');
                $table->string('name');
                $table->json('display_conditions')->nullable();
                $table->json('builder_data')->nullable();
                $table->longText('html_content')->nullable();
                $table->enum('status', ['draft', 'publish'])->default('draft');
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index('type');
                $table->index('status');
                $table->index(['type', 'status']);
            });

            $connection->statement('INSERT INTO site_layouts_new SELECT * FROM site_layouts');
            Schema::drop('site_layouts');
            Schema::rename('site_layouts_new', 'site_layouts');
        } else {
            Schema::table('site_layouts', function (Blueprint $table) {
                $table->enum('type', ['header', 'footer'])->default('header')->change();
            });
        }
    }
};
