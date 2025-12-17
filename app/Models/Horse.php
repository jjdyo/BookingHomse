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
        'photo_path',
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

    public function getPhotoUrlAttribute(): ?string
    {
        if (! $this->photo_path) {
            return null;
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->url($this->photo_path);
    }
}
