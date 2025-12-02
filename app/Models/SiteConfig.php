<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_name',
        'booking_open_time',
        'booking_close_time',
        'logo_path',
    ];

    public static function instance(): self
    {
        // Always ensure a single persisted row exists and return it
        return static::query()->firstOrCreate(
            [],
            [
                'site_name' => 'Booking Homse',
                'booking_open_time' => '09:00:00',
                'booking_close_time' => '19:00:00',
            ]
        );
    }

    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo_path) return null;
        return \Illuminate\Support\Facades\Storage::disk('public')->url($this->logo_path);
    }
}
