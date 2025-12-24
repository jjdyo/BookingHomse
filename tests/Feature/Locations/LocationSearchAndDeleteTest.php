<?php

namespace Tests\Feature\Locations;

use App\Models\Location;
use App\Models\Timeslot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LocationSearchAndDeleteTest extends TestCase
{
    use RefreshDatabase;

    private function login(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    public function test_search_returns_active_locations_by_name_only(): void
    {
        $this->login();

        Location::factory()->create(['name' => 'North Arena', 'description' => 'd', 'is_active' => true]);
        Location::factory()->create(['name' => 'South Barn', 'description' => 'd', 'is_active' => false]); // inactive
        Location::factory()->create(['name' => 'East Paddock', 'description' => 'd', 'is_active' => true]);

        // search "north" should match only North Arena because we search by name
        $json = $this->getJson('/locations/search?q=north&limit=10')
            ->assertOk()
            ->json();

        $this->assertCount(1, $json);
        $this->assertSame('North Arena', $json[0]['name']);
    }

    public function test_destroy_blocks_when_future_timeslots_exist_and_allows_when_only_past(): void
    {
        $this->login();

        $loc = Location::factory()->create(['name' => 'Delete Guard Loc', 'description' => 'd']);

        // Past timeslot — should not block deletion
        Timeslot::create([
            'title' => 'Past Lesson',
            'description' => 'd',
            'start_at' => Carbon::now()->subDays(5),
            'end_at' => Carbon::now()->subDays(5)->addHour(),
            'location_id' => $loc->id,
        ]);

        // Attempt delete now: should succeed
        $this->delete('/locations/'.$loc->id)->assertRedirect();
        $this->assertDatabaseMissing('locations', ['id' => $loc->id]);

        // Create another location
        $loc2 = Location::factory()->create(['name' => 'Guarded Loc', 'description' => 'd']);
        // Future timeslot — should block deletion
        Timeslot::create([
            'title' => 'Future Lesson',
            'description' => 'd',
            'start_at' => Carbon::now()->addDays(5),
            'end_at' => Carbon::now()->addDays(5)->addHour(),
            'location_id' => $loc2->id,
        ]);

        $this->from('/dashboard/locations')->delete('/locations/'.$loc2->id)
            ->assertSessionHas('error');
        $this->assertDatabaseHas('locations', ['id' => $loc2->id]);
    }
}
