<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Horse extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'breed',
        'is_bookable',
        'notes',
    ];

    protected $casts = [
        'is_bookable' => 'boolean',
    ];

    /**
     * Scope: order horses for consistent dashboard listing.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('name');
    }
}
