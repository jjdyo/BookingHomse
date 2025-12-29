<?php

namespace Tests\Feature\Timeslots;

use App\Models\Timeslot;
use App\Models\Trainer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TimeslotMultiTrainersTest extends TestCase
{
    use RefreshDatabase;

    private function actingUser(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    public function test_create_timeslot_with_multiple_trainers_attaches_pivot_and_feed_contains_names(): void
    {
        $this->actingUser();

        $t1 = Trainer::factory()->create(['name' => 'Alex Rider', 'title' => 'Senior Coach']);
        $t2 = Trainer::factory()->create(['name' => 'Jamie Fox', 'title' => 'Assistant Coach']);

        $payload = [
            'title' => 'Lesson 101',
            'description' => 'Intro class',
            'start_at' => Carbon::parse('2025-12-20 10:00:00')->toIso8601String(),
            'end_at' => Carbon::parse('2025-12-20 11:00:00')->toIso8601String(),
            'capacity' => 2,
            'price' => 50,
            'trainer_ids' => [$t1->id, $t2->id],
        ];

        $this->post('/timeslots', $payload)->assertRedirect();

        $timeslot = Timeslot::firstOrFail();
        $this->assertEqualsCanonicalizing([$t1->id, $t2->id], $timeslot->trainers()->pluck('trainers.id')->all());

        // Feed should include trainer_names[] and trainer_label
        $feed = $this->get('/timeslots/feed?rangeStart=2025-12-20T00:00:00Z&rangeEnd=2025-12-21T00:00:00Z')
            ->assertOk()
            ->json();

        $this->assertNotEmpty($feed);
        $event = collect($feed)->firstWhere('id', $timeslot->id);
        $this->assertNotNull($event);
        $this->assertEqualsCanonicalizing(['Alex Rider', 'Jamie Fox'], $event['extendedProps']['trainer_names']);
        $this->assertEquals('Alex Rider, Jamie Fox', $event['extendedProps']['trainer_label']);
    }

    public function test_edit_payload_includes_trainer_ids_and_details_with_photo_url(): void
    {
        $this->actingUser();

        $t = Trainer::factory()->create(['name' => 'Jordan Page', 'title' => 'Coach', 'photo_path' => 'images/trainer.jpg']);
        $slot = Timeslot::factory()->create([
            'start_at' => Carbon::parse('2025-12-21 13:00:00'),
            'end_at' => Carbon::parse('2025-12-21 14:00:00'),
        ]);
        $slot->trainers()->sync([$t->id]);

        $res = $this->get("/dashboard/timeslots/{$slot->id}/edit")->assertOk();
        $page = $res->getOriginalContent()->getData()['page'];
        $props = $page['props'];

        $this->assertEquals([$t->id], $props['timeslot']['trainer_ids']);
        $this->assertIsArray($props['timeslot']['trainers']);
        $this->assertSame('Jordan Page', $props['timeslot']['trainers'][0]['name']);
        $this->assertSame('Coach', $props['timeslot']['trainers'][0]['title']);
        // photo_url accessor should be present (may be absolute or storage URL)
        $this->assertArrayHasKey('photo_url', $props['timeslot']['trainers'][0]);

        // location fields should be present for edit UI prefill
        $this->assertArrayHasKey('location_id', $props['timeslot']);
        $this->assertTrue(array_key_exists('location_name', $props['timeslot']));
    }

    public function test_update_timeslot_syncs_trainer_ids(): void
    {
        $this->actingUser();

        $t1 = Trainer::factory()->create(['name' => 'Sam']);
        $t2 = Trainer::factory()->create(['name' => 'Morgan']);
        $t3 = Trainer::factory()->create(['name' => 'Taylor']);

        $slot = Timeslot::factory()->create([
            'start_at' => Carbon::parse('2025-12-22 09:00:00'),
            'end_at' => Carbon::parse('2025-12-22 10:00:00'),
        ]);
        $slot->trainers()->sync([$t1->id, $t2->id]);

        $payload = [
            'title' => $slot->title,
            'description' => $slot->description,
            'start_at' => $slot->start_at->toIso8601String(),
            'end_at' => $slot->end_at->toIso8601String(),
            'capacity' => $slot->capacity,
            'is_group' => $slot->is_group,
            'price' => $slot->price,
            'service_name' => $slot->service_name,
            'trainer_ids' => [$t3->id],
            'location_id' => $slot->location_id,
            'horse_ids' => [],
        ];

        $this->put("/timeslots/{$slot->id}", $payload)->assertRedirect();

        $this->assertEqualsCanonicalizing([$t3->id], $slot->fresh()->trainers()->pluck('trainers.id')->all());
    }

    public function test_trainer_conflicts_with_multiple_selected_trainers(): void
    {
        $this->actingUser();

        $a = Trainer::factory()->create(['name' => 'A']);
        $b = Trainer::factory()->create(['name' => 'B']);

        // Existing overlapping slot with trainer A only
        $slot = Timeslot::factory()->create([
            'start_at' => Carbon::parse('2025-12-23 10:00:00'),
            'end_at' => Carbon::parse('2025-12-23 11:00:00'),
        ]);
        $slot->trainers()->sync([$a->id]);

        $payload = [
            'start_at' => '2025-12-23T10:30:00Z',
            'end_at' => '2025-12-23T11:30:00Z',
            'trainer_ids' => [$a->id, $b->id],
        ];

        $this->postJson('/timeslots/check-conflicts', $payload)
            ->assertOk()
            ->assertJson(fn ($json) => $json
                ->has('conflicts.trainers', 1)
            );
    }
}
