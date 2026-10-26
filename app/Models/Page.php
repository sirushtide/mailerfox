<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Page extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_id',
        'title',
        'slug',
        'custom_domain',
        'type',
        'variant_key',
        'status',
        'html_content',
        'builder_data',
    ];

    protected function casts(): array
    {
        return [
            'builder_data' => 'array',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($page) {
            if (empty($page->slug) && !empty($page->title)) {
                $page->slug = Str::slug($page->title);
            }

            if (!empty($page->slug)) {
                $originalSlug = $page->slug;
                $count = 1;
                while (static::withTrashed()->where('slug', $page->slug)->exists()) {
                    $page->slug = $originalSlug . '-' . $count;
                    $count++;
                }
            }
        });
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'publish');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function supabaseIntegration()
    {
        return $this->hasOne(SupabaseIntegration::class);
    }
}
