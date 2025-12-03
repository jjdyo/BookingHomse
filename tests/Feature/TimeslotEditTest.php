<?php

namespace Tests\Feature;

use App\Models\Timeslot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimeslotEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_update_timeslot_and_feed_reflects_changes(): void
    {
        $user = User::factory()->create();
        $slot = Timeslot::factory()->create([
            'title' => 'Lesson',
            'description' => 'Desc',
            'price' => 10,
        ]);

        $this->actingAs($user);

        $response = $this->put("/timeslots/{$slot->id}", [
            'title' => 'Updated Lesson',
            'description' => 'New Desc',
            'start_at' => $slot->start_at->copy()->addHour()->toISOString(),
            'end_at' => $slot->end_at->copy()->addHour()->toISOString(),
            'capacity' => 2,
            'is_group' => true,
            'price' => 25.50,
            'service_name' => 'Beginner',
            'trainer_name' => 'Alex',
            'location_id' => null,
        ]);

        $response->assertRedirect(route('dashboard.timeslots'));

        $this->assertDatabaseHas('timeslots', [
            'id' => $slot->id,
            'title' => 'Updated Lesson',
            'description' => 'New Desc',
        ]);

        // Feed should include updated event
        $feed = $this->getJson('/timeslots/feed');
        $feed->assertOk();
        $this->assertTrue(collect($feed->json())->contains(fn ($e) => $e['id'] === $slot->id && $e['title'] === 'Updated Lesson'));
    }
}
