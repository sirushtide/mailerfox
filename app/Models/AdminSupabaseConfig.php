<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminSupabaseConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'connection_type',
        'project_url',
        'project_id',
        'publishable_key',
        'secret_key',
        'db_password',
        'region',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'is_default',
        'status',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'token_expires_at' => 'datetime',
            'publishable_key' => 'encrypted',
            'secret_key' => 'encrypted',
            'db_password' => 'encrypted',
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
        ];
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeConnected($query)
    {
        return $query->where('status', 'connected');
    }
}
