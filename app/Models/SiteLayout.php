<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteLayout extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'name',
        'display_conditions',
        'builder_data',
        'html_content',
        'status',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'display_conditions' => 'array',
            'builder_data' => 'array',
        ];
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'publish');
    }

    public function scopeHeaders($query)
    {
        return $query->where('type', 'header');
    }

    public function scopeFooters($query)
    {
        return $query->where('type', 'footer');
    }

    public function scopeMegaMenus($query)
    {
        return $query->where('type', 'megamenu');
    }
}
