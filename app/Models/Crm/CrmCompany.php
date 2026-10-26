<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmCompany extends Model
{
    use HasFactory;

    protected $table = 'crm_companies';

    protected $fillable = [
        'workspace_id',
        'name',
        'slug',
        'email',
        'phone',
        'website',
        'industry',
        'size',
        'address',
        'description',
        'logo_path',
        'status',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Workspace::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(CrmContact::class, 'company_id');
    }

    public function activeContacts(): HasMany
    {
        return $this->hasMany(CrmContact::class, 'company_id')
            ->whereIn('status', ['lead', 'prospect', 'customer']);
    }
}
