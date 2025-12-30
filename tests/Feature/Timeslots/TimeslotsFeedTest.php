<?php

namespace Tests\Feature\Timeslots;

use App\Models\Horse;
use App\Models\Location;
use App\Models\Timeslot;
use App\Models\Trainer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimeslotsFeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_feed_returns_timeslots_in_expected_shape(): void
    {
        $start = now()->addDay()->startOfHour();
        $end = (clone $start)->addHour();

        $loc = Location::factory()->create(['name' => 'Arena A', 'description' => 'Main arena', 'address' => '123 Farm Rd']);

        $slot = Timeslot::create([
            'title' => 'Lesson',
            'description' => 'Intro ride',
            'start_at' => $start,
            'end_at' => $end,
            'capacity' => 2,
            'price' => 45.50,
            'service_name' => 'Private Lesson',
            'location_id' => $loc->id,
        ]);
        $trainer = Trainer::factory()->create(['name' => 'Alex']);
        $slot->trainers()->sync([$trainer->id]);

        // FullCalendar passes ISO8601 with timezone; our controller compares strings, so use exact window that overlaps
        $json = $this->getJson('/timeslots/feed?start='.$start->copy()->subMinutes(1)->toIso8601String().'&end='.$end->copy()->addMinutes(1)->toIso8601String())
            ->assertOk()
            ->json();

        $this->assertIsArray($json);
        $this->assertCount(1, $json);
        $first = $json[0];
        $this->assertSame($slot->id, $first['id']);
        $this->assertSame('Lesson', $first['title']);
        $this->assertArrayHasKey('start', $first);
        $this->assertArrayHasKey('end', $first);
        $this->assertArrayHasKey('extendedProps', $first);
        $this->assertSame('Intro ride', $first['extendedProps']['description']);
        $this->assertSame(2, $first['extendedProps']['capacity']);
        $this->assertEquals(45.50, $first['extendedProps']['price']);
        $this->assertSame('Private Lesson', $first['extendedProps']['service_name']);
        $this->assertEquals(['Alex'], $first['extendedProps']['trainer_names']);
        $this->assertSame('Alex', $first['extendedProps']['trainer_label']);
        $this->assertSame('Arena A', $first['extendedProps']['location_name']);
        $this->assertSame('123 Farm Rd', $first['extendedProps']['location_address']);
    }

    public function test_feed_returns_timeslots_with_horse_data(): void
    {
        $start = now()->addDay()->startOfHour();
        $end = (clone $start)->addHour();

        $slot = Timeslot::create([
            'title' => 'Horse Lesson',
            'description' => 'Test description',
            'start_at' => $start,
            'end_at' => $end,
        ]);

        $horse1 = Horse::factory()->create(['name' => 'Spirit']);
        $horse2 = Horse::factory()->create(['name' => 'Rain']);
        $slot->horses()->sync([$horse1->id, $horse2->id]);

        $json = $this->getJson('/timeslots/feed')->assertOk()->json();

        $this->assertCount(1, $json);
        $props = $json[0]['extendedProps'];

        $this->assertEquals(['Spirit', 'Rain'], $props['horse_names']);
        $this->assertSame('Spirit, Rain', $props['horse_label']);
    }

    public function test_feed_returns_timeslots_with_multi_trainer_data(): void
    {
        $start = now()->addDay()->startOfHour();
        $end = (clone $start)->addHour();

        $slot = Timeslot::create([
            'title' => 'Group Session',
            'description' => 'Test description',
            'start_at' => $start,
            'end_at' => $end,
        ]);

        $trainer1 = Trainer::factory()->create(['name' => 'Trainer A']);
        $trainer2 = Trainer::factory()->create(['name' => 'Trainer B']);
        $slot->trainers()->sync([$trainer1->id, $trainer2->id]);

        $json = $this->getJson('/timeslots/feed')->assertOk()->json();

        $this->assertCount(1, $json);
        $props = $json[0]['extendedProps'];

        $this->assertEquals(['Trainer A', 'Trainer B'], $props['trainer_names']);
        $this->assertSame('Trainer A, Trainer B', $props['trainer_label']);
    }
}
