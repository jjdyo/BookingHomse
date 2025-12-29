<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TimeslotPreset extends Model
{
    use HasFactory;

    protected $fillable = [
        'preset_title',
        'preset_description',
        'title',
        'description',
        'capacity',
        'is_group',
        'price',
        // Deprecated single-trainer fields retained for backward-compat reads only
        'trainer_id',
        'trainer_name',
        'service_name',
        'location_id',
        'color',
    ];

    protected $casts = [
        'is_group' => 'boolean',
        'price' => 'decimal:2',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function horses(): BelongsToMany
    {
        return $this->belongsToMany(Horse::class, 'timeslot_preset_horse');
    }

    public function trainers(): BelongsToMany
    {
        return $this->belongsToMany(Trainer::class, 'timeslot_preset_trainer');
    }
}
