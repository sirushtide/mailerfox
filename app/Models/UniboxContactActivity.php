<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UniboxContactActivity extends Model
{
    protected $fillable = ['contact_id', 'customer_id', 'type', 'description', 'meta', 'occurred_at'];

    protected $casts = [
        'meta' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(UniboxContact::class, 'contact_id');
    }
}
