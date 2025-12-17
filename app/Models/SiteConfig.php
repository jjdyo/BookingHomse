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
        'warn_overbook_trainers',
        'warn_overbook_horses',
        'warn_overbook_timeslots',
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
                'warn_overbook_trainers' => true,
                'warn_overbook_horses' => true,
                'warn_overbook_timeslots' => true,
            ]
        );
    }

    public function getLogoUrlAttribute(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->url($this->logo_path);
    }
}
