<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UniboxReplyTemplate extends Model
{
    protected $fillable = [
        'customer_id',
        'folder',
        'name',
        'subject',
        'body',
        'shared',
    ];

    protected function casts(): array
    {
        return [
            'shared' => 'boolean',
        ];
    }
}
