<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitorTrackingEvent extends Model
{
    public $timestamps = false;

    protected $table = 'visitor_tracking_events';

    protected $fillable = [
        'visitor_tracking_session_id',
        'event_type',
        'url',
        'route_name',
        'method',
        'element_label',
        'element_selector',
        'element_track_id',
        'metadata',
        'duration_seconds',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(VisitorTrackingSession::class, 'visitor_tracking_session_id');
    }
}
