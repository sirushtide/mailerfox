<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnrichmentJob extends Model
{
    protected $fillable = [
        'scraper_job_id',
        'customer_id',
        'status',
        'total_leads',
        'processed',
        'failed',
    ];

    protected function casts(): array
    {
        return [
            'total_leads' => 'integer',
            'processed' => 'integer',
            'failed' => 'integer',
        ];
    }

    public function scraperJob(): BelongsTo
    {
        return $this->belongsTo(ScraperJob::class, 'scraper_job_id');
    }
}
