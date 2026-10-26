<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UniboxTag extends Model
{
    protected $fillable = ['customer_id', 'account_id', 'name', 'color'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
