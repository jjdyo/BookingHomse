<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Horse;
use App\Models\Timeslot;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        return [
            'timeslot_id' => Timeslot::factory(),
            'user_id' => User::factory(), // client/customer
            'horse_id' => null,

            // default lifecycle state
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'paid_at' => null,

            // cancellation metadata
            'cancelled_at' => null,
            'cancelled_by' => null,
            'cancel_reason' => null,
        ];
    }

    /**
     * Attach a horse to this booking.
     */
    public function withHorse(): self
    {
        return $this->state(fn () => [
            'horse_id' => Horse::factory(),
        ]);
    }

    /**
     * Mark the booking as confirmed.
     */
    public function confirmed(): self
    {
        return $this->state(fn () => [
            'status' => 'confirmed',
        ]);
    }

    /**
     * Mark the booking as completed.
     */
    public function completed(): self
    {
        return $this->state(fn () => [
            'status' => 'completed',
        ]);
    }

    /**
     * Mark the booking as a no-show.
     */
    public function noShow(): self
    {
        return $this->state(fn () => [
            'status' => 'no_show',
        ]);
    }

    /**
     * Cancel the booking.
     * Optionally provide a specific user who cancelled and a reason.
     */
    public function cancelled(?User $by = null, ?string $reason = null): self
    {
        return $this->state(function () use ($by, $reason) {
            return [
                'status' => 'cancelled',
                'cancelled_at' => Carbon::now(),
                // If a concrete User model is provided, use its id; otherwise have the factory create one.
                'cancelled_by' => $by?->id ?? User::factory(),
                'cancel_reason' => $reason ?? $this->faker->sentence(8),
            ];
        });
    }

    /**
     * Mark the booking as paid.
     */
    public function paid(): self
    {
        return $this->state(function () {
            $paidAt = Carbon::now();

            return [
                'payment_status' => 'paid',
                'paid_at' => $paidAt,
            ];
        });
    }

    /**
     * Mark the booking as refunded.
     */
    public function refunded(): self
    {
        return $this->state(fn () => [
            'payment_status' => 'refunded',
            'paid_at' => null,
        ]);
    }
}
