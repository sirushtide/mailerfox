<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VisitorTrackingSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_uid',
        'customer_id',
        'ip',
        'user_agent',
        'ip_geolocation_id',
        'country',
        'country_code',
        'city',
        'is_guest',
        'started_at',
        'last_activity_at',
        'ended_at',
        'page_views_count',
        'clicks_count',
        'duration_seconds',
        'metadata',
    ];

    protected $casts = [
        'is_guest' => 'boolean',
        'started_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'ended_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function ipGeolocation(): BelongsTo
    {
        return $this->belongsTo(IpGeolocation::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(VisitorTrackingEvent::class, 'visitor_tracking_session_id');
    }

    public function pageViews()
    {
        return $this->events()->where('event_type', 'page_view');
    }

    public function clicks()
    {
        return $this->events()->where('event_type', 'click');
    }
}
