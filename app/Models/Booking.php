<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'timeslot_id',
        'user_id',
        'horse_id',
        'status',
        'payment_status',
        'paid_at',
        'cancelled_at',
        'cancelled_by',
        'cancel_reason',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function timeslot()
    {
        return $this->belongsTo(Timeslot::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function horse()
    {
        return $this->belongsTo(Horse::class);
    }
}
