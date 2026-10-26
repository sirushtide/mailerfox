<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupabaseIntegration extends Model
{
    use HasFactory;

    protected $fillable = [
        'page_id',
        'customer_id',
        'connection_type',
        'mode',
        'admin_supabase_config_id',
        'project_url',
        'project_id',
        'publishable_key',
        'secret_key',
        'db_password',
        'region',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'status',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'publishable_key' => 'encrypted',
            'secret_key' => 'encrypted',
            'db_password' => 'encrypted',
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'token_expires_at' => 'datetime',
            'auth_providers' => 'array',
        ];
    }

    public function page()
    {
        return $this->belongsTo(Page::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function adminSupabaseConfig()
    {
        return $this->belongsTo(AdminSupabaseConfig::class);
    }

    public function isAdminManaged(): bool
    {
        return $this->mode === 'admin_managed';
    }

    public function isByosManual(): bool
    {
        return $this->mode === 'byos_manual';
    }

    public function isByosOAuth(): bool
    {
        return $this->mode === 'byos_oauth';
    }
}
