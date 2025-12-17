<?php

namespace Tests\Feature\Timeslots;

use App\Models\Horse;
use App\Models\Timeslot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimeslotStoreAttachHorsesTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_timeslot_attaches_selected_horses(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $h1 = Horse::factory()->create(['is_bookable' => true]);
        $h2 = Horse::factory()->create(['is_bookable' => true]);

        $payload = [
            'title' => 'Lesson 101',
            'description' => 'Intro lesson',
            'start_at' => now()->addDay()->startOfHour()->toISOString(),
            'end_at' => now()->addDay()->startOfHour()->addHour()->toISOString(),
            'capacity' => 2,
            'price' => 50,
            'trainer_name' => 'Alex Doe',
            'horse_ids' => [$h1->id, $h2->id],
        ];

        $resp = $this->post('/timeslots', $payload);
        $resp->assertRedirect();

        $slot = Timeslot::query()->latest('id')->first();
        $this->assertNotNull($slot);
        $this->assertCount(2, $slot->horses);
        $this->assertTrue($slot->horses->pluck('id')->contains($h1->id));
        $this->assertTrue($slot->horses->pluck('id')->contains($h2->id));
    }
}
