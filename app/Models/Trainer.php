<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trainer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'title',
        'bio',
        'photo_path',
        'is_bookable',
    ];

    /**
     * Scope: order trainers for consistent dashboard listing.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('name');
    }

    /**
     * Casts for attributes.
     */
    protected function casts(): array
    {
        return [
            'is_bookable' => 'boolean',
        ];
    }

    /**
     * Scope: only trainers marked as bookable.
     */
    public function scopeBookable($query)
    {
        return $query->where('is_bookable', true);
    }
}
