<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrichment_jobs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('scraper_job_id');
            $table->unsignedBigInteger('customer_id');
            $table->string('status', 30)->default('pending');
            $table->unsignedInteger('total_leads')->default(0);
            $table->unsignedInteger('processed')->default(0);
            $table->unsignedInteger('failed')->default(0);
            $table->timestamps();

            $table->index(['scraper_job_id']);
            $table->index(['customer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrichment_jobs');
    }
};
