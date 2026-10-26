<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unibox_reply_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->string('folder')->nullable();
            $table->string('name');
            $table->string('subject')->nullable();
            $table->text('body');
            $table->boolean('shared')->default(false);
            $table->timestamps();

            $table->index(['customer_id', 'folder']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unibox_reply_templates');
    }
};
