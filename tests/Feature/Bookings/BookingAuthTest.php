<?php

namespace Tests\Feature\Bookings;

use App\Models\Timeslot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_visiting_book_timeslot(): void
    {
        $slot = Timeslot::create([
            'title' => 'Test Slot',
            'description' => 'Testing',
            'start_at' => now()->addDay(),
            'end_at' => now()->addDay()->addHour(),
            'capacity' => 1,
            'price' => 0,
        ]);

        $this->get('/book/timeslot/' . $slot->id)
            ->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_placeholder_booking_page(): void
    {
        $user = User::factory()->create();
        $slot = Timeslot::create([
            'title' => 'Test Slot',
            'description' => 'Desc',
            'start_at' => now()->addDay(),
            'end_at' => now()->addDay()->addHour(),
            'capacity' => 1,
            'price' => 10,
        ]);

        $this->actingAs($user)
            ->get('/book/timeslot/' . $slot->id)
            ->assertOk()
            ->assertSee('Test Slot');
    }
}
