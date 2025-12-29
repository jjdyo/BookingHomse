<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

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

    /**
     * Accessor: full URL to the trainer's photo, if set.
     */
    public function getPhotoUrlAttribute(): ?string
    {
        if (! $this->photo_path) {
            return null;
        }

        try {
            return Storage::url($this->photo_path);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
