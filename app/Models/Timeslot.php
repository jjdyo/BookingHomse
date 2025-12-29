<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Timeslot extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'start_at',
        'end_at',
        'capacity',
        'is_group',
        'price',
        'created_by',
        // Deprecated single-trainer fields retained for backward-compat reads only
        'trainer_id',
        'trainer_name',
        'service_name',
        'location_id',
        'color',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'is_group' => 'boolean',
        'price' => 'decimal:2',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function horses(): BelongsToMany
    {
        return $this->belongsToMany(Horse::class, 'timeslot_horse');
    }

    public function trainers(): BelongsToMany
    {
        return $this->belongsToMany(Trainer::class, 'timeslot_trainer');
    }
}
