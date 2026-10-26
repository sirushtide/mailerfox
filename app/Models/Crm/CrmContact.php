<?php

namespace App\Models\Crm;

use App\Models\ListSubscriber;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmContact extends Model
{
    use HasFactory;

    protected $table = 'crm_contacts';

    protected $fillable = [
        'workspace_id',
        'company_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'title',
        'source',
        'status',
        'notes',
        'custom_fields',
        'last_activity_at',
    ];

    protected function casts(): array
    {
        return [
            'custom_fields' => 'array',
            'last_activity_at' => 'datetime',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Workspace::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(CrmCompany::class, 'company_id');
    }

    public function deals(): HasMany
    {
        return $this->hasMany(CrmDeal::class, 'contact_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(CrmTask::class, 'contact_id');
    }

    public function notes(): HasMany
    {
        return $this->morphMany(CrmNote::class, 'notable');
    }

    public function subscribers(): BelongsToMany
    {
        return $this->belongsToMany(ListSubscriber::class, 'crm_contact_subscriber', 'contact_id', 'subscriber_id');
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}") ?: ($this->email ?: 'Unknown');
    }

    public function touchActivity(): void
    {
        $this->update(['last_activity_at' => now()]);
    }
}
