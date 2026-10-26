<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_contact_subscriber', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained('crm_contacts')->cascadeOnDelete();
            $table->foreignId('subscriber_id')->constrained('list_subscribers')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['contact_id', 'subscriber_id']);
            $table->index('contact_id');
            $table->index('subscriber_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_contact_subscriber');
    }
};
