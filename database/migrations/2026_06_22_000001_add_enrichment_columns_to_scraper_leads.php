<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scraper_leads', function (Blueprint $table) {
            $table->enum('enrichment_status', ['pending', 'enriching', 'enriched', 'failed', 'none'])->default('none')->after('raw_data');
            $table->timestamp('enriched_at')->nullable()->after('enrichment_status');
            $table->json('enrichment_data')->nullable()->after('enriched_at');
            $table->json('enrichment_sources')->nullable()->after('enrichment_data');
            $table->unsignedTinyInteger('confidence_score')->nullable()->after('enrichment_sources');
            $table->string('linkedin_url')->nullable()->after('confidence_score');
            $table->string('job_title')->nullable()->after('linkedin_url');
            $table->string('company_size')->nullable()->after('job_title');
            $table->string('industry')->nullable()->after('company_size');
            $table->string('location')->nullable()->after('industry');
            $table->json('technologies')->nullable()->after('location');
            $table->json('social_profiles')->nullable()->after('technologies');
            $table->string('funding')->nullable()->after('social_profiles');
            $table->string('employee_count')->nullable()->after('funding');

            $table->index(['customer_id', 'enrichment_status']);
        });
    }

    public function down(): void
    {
        Schema::table('scraper_leads', function (Blueprint $table) {
            $table->dropIndex(['customer_id', 'enrichment_status']);
            $table->dropColumn([
                'enrichment_status',
                'enriched_at',
                'enrichment_data',
                'enrichment_sources',
                'confidence_score',
                'linkedin_url',
                'job_title',
                'company_size',
                'industry',
                'location',
                'technologies',
                'social_profiles',
                'funding',
                'employee_count',
            ]);
        });
    }
};
