<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unibox_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->string('name');
            $table->string('email');
            $table->enum('type', ['gmail', 'outlook', 'imap'])->default('imap');
            // IMAP (incoming)
            $table->string('imap_host')->nullable();
            $table->unsignedSmallInteger('imap_port')->default(993);
            $table->enum('imap_encryption', ['ssl', 'tls', 'none'])->default('ssl');
            $table->string('imap_username')->nullable();
            $table->text('imap_password')->nullable();
            // SMTP (outgoing)
            $table->string('smtp_host')->nullable();
            $table->unsignedSmallInteger('smtp_port')->default(587);
            $table->enum('smtp_encryption', ['tls', 'ssl', 'none'])->default('tls');
            $table->string('smtp_username')->nullable();
            $table->text('smtp_password')->nullable();
            // OAuth
            $table->text('oauth_token')->nullable();
            $table->text('oauth_refresh_token')->nullable();
            $table->timestamp('oauth_expires_at')->nullable();
            // State
            $table->enum('status', ['active', 'paused', 'error'])->default('active');
            $table->string('last_error')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->string('last_uid')->nullable();
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->index('customer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unibox_accounts');
    }
};
