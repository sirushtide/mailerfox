<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unibox_contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->string('email')->index();
            $table->string('name')->nullable();
            $table->string('company')->nullable();
            $table->string('job_title')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->string('location')->nullable();
            $table->string('timezone')->nullable();
            $table->string('avatar_url')->nullable();
            $table->json('tags')->nullable();
            $table->decimal('revenue', 12, 2)->default(0);
            $table->unsignedInteger('conversation_count')->default(0);
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_reply_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->unique(['customer_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unibox_contacts');
    }
};
