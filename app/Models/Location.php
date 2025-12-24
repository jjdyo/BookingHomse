<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'address',
        'notes',
        'photo_path',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Accessors
    public function getPhotoUrlAttribute(): ?string
    {
        if (! $this->photo_path) {
            return null;
        }
        try {
            return \Illuminate\Support\Facades\Storage::disk(config('media-manager.disk', 'public'))
                ->url($this->photo_path);
        } catch (\Throwable $e) {
            // Missing file or disk misconfiguration — return null so UI can show placeholder
            return null;
        }
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearchByName($query, ?string $q)
    {
        if (! $q) {
            return $query;
        }

        return $query->where('name', 'like', "%{$q}%");
    }
}
