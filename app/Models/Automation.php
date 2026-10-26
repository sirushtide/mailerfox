<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\WorkspaceScoped;

class Automation extends Model
{
    use HasFactory, SoftDeletes, WorkspaceScoped;

    protected $fillable = [
        'customer_id',
        'name',
        'status',
        'graph',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'graph' => 'array',
            'settings' => 'array',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function runs(): HasMany
    {
        return $this->hasMany(AutomationRun::class);
    }
}
