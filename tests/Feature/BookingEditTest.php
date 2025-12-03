<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Horse;
use App\Models\Timeslot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_update_booking_status_payment_and_cancellation_metadata(): void
    {
        $user = User::factory()->create();
        $customer = User::factory()->create();
        $horse = Horse::factory()->create();
        $slot = Timeslot::factory()->create();

        $booking = Booking::create([
            'timeslot_id' => $slot->id,
            'user_id' => $customer->id,
            'horse_id' => null,
            'status' => 'pending',
            'payment_status' => 'unpaid',
        ]);

        $this->actingAs($user);

        // First, mark paid and assign horse
        $response = $this->put("/bookings/{$booking->id}", [
            'timeslot_id' => $slot->id,
            'user_id' => $customer->id,
            'horse_id' => $horse->id,
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'cancel_reason' => null,
        ]);

        $response->assertRedirect(route('dashboard.timeslots'));
        $booking->refresh();
        $this->assertEquals('confirmed', $booking->status);
        $this->assertEquals('paid', $booking->payment_status);
        $this->assertNotNull($booking->paid_at); // auto-set
        $this->assertNull($booking->cancelled_at);
        $this->assertNull($booking->cancelled_by);

        // Then cancel with a reason
        $response = $this->put("/bookings/{$booking->id}", [
            'timeslot_id' => $slot->id,
            'user_id' => $customer->id,
            'horse_id' => $horse->id,
            'status' => 'cancelled',
            'payment_status' => 'paid',
            'cancel_reason' => 'Weather',
        ]);
        $response->assertRedirect(route('dashboard.timeslots'));
        $booking->refresh();
        $this->assertEquals('cancelled', $booking->status);
        $this->assertNotNull($booking->cancelled_at);
        $this->assertEquals($user->id, $booking->cancelled_by);
        $this->assertEquals('Weather', $booking->cancel_reason);

        // Reactivate: cancellation fields should clear
        $response = $this->put("/bookings/{$booking->id}", [
            'timeslot_id' => $slot->id,
            'user_id' => $customer->id,
            'horse_id' => $horse->id,
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'cancel_reason' => null,
        ]);
        $response->assertRedirect(route('dashboard.timeslots'));
        $booking->refresh();
        $this->assertEquals('confirmed', $booking->status);
        $this->assertNull($booking->cancelled_at);
        $this->assertNull($booking->cancelled_by);
        $this->assertNull($booking->cancel_reason);
    }
}
