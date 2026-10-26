<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiBuilderSession extends Model
{
    use HasFactory;

    protected $table = 'ai_builder_sessions';

    protected $fillable = [
        'admin_user_id',
        'customer_id',
        'session_id',
        'messages',
        'preview_html',
        'project_files',
        'react_code',
        'preview_mode',
        'preview_title',
        'page_title',
        'page_slug',
        'page_status',
        'page_id',
        'model',
        'app_meta',
    ];

    protected $casts = [
        'messages' => 'array',
        'project_files' => 'array',
        'app_meta' => 'array',
    ];

    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
