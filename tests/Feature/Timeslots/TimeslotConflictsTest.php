<?php

namespace Tests\Feature\Timeslots;

use App\Models\Horse;
use App\Models\Timeslot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TimeslotConflictsTest extends TestCase
{
    use RefreshDatabase;

    private function actingUser(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    public function test_timeslot_overlap_conflict_is_reported(): void
    {
        $this->actingUser();

        // Existing timeslot: 10:00 - 11:00
        $existing = Timeslot::factory()->create([
            'start_at' => Carbon::parse('2025-12-20 10:00:00'),
            'end_at' => Carbon::parse('2025-12-20 11:00:00'),
            'trainer_name' => 'Sam Coach',
        ]);

        // New proposal: 10:30 - 11:30 (overlaps)
        $payload = [
            'start_at' => '2025-12-20T10:30:00Z',
            'end_at' => '2025-12-20T11:30:00Z',
        ];

        $this->postJson('/timeslots/check-conflicts', $payload)
            ->assertOk()
            ->assertJson(fn ($json) => $json
                ->has('conflicts.timeslots', 1)
            );
    }

    public function test_boundary_touch_does_not_conflict(): void
    {
        $this->actingUser();

        // Existing: 10:00-11:00
        Timeslot::factory()->create([
            'start_at' => Carbon::parse('2025-12-21 10:00:00'),
            'end_at' => Carbon::parse('2025-12-21 11:00:00'),
        ]);

        // New: 11:00-12:00 (touches boundary, should NOT warn)
        $payload = [
            'start_at' => '2025-12-21T11:00:00Z',
            'end_at' => '2025-12-21T12:00:00Z',
        ];

        $this->postJson('/timeslots/check-conflicts', $payload)
            ->assertOk()
            ->assertJson(fn ($json) => $json
                ->where('conflicts.timeslots', [])
                ->etc()
            );
    }

    public function test_trainer_conflict_requires_direct_match(): void
    {
        $this->actingUser();

        Timeslot::factory()->create([
            'start_at' => Carbon::parse('2025-12-22 10:00:00'),
            'end_at' => Carbon::parse('2025-12-22 11:00:00'),
            'trainer_name' => 'Alex Doe',
        ]);

        $payload = [
            'start_at' => '2025-12-22T10:30:00Z',
            'end_at' => '2025-12-22T11:30:00Z',
            'trainer_name' => 'Alex Doe', // direct match required
        ];

        $this->postJson('/timeslots/check-conflicts', $payload)
            ->assertOk()
            ->assertJson(fn ($json) => $json
                ->has('conflicts.trainers', 1)
            );
    }

    public function test_trainer_conflict_does_not_match_when_case_or_whitespace_differs(): void
    {
        $this->actingUser();

        Timeslot::factory()->create([
            'start_at' => Carbon::parse('2025-12-24 10:00:00'),
            'end_at' => Carbon::parse('2025-12-24 11:00:00'),
            'trainer_name' => 'Alex Doe',
        ]);

        // Different case and extra spaces should NOT match per requirement
        $payload = [
            'start_at' => '2025-12-24T10:30:00Z',
            'end_at' => '2025-12-24T11:30:00Z',
            'trainer_name' => '  alex   doe  ',
        ];

        $this->postJson('/timeslots/check-conflicts', $payload)
            ->assertOk()
            ->assertJson(fn ($json) => $json
                ->where('conflicts.trainers', [])
                ->etc()
            );
    }

    public function test_multiple_overlaps_are_all_reported_in_timeslots_and_trainers(): void
    {
        $this->actingUser();

        // Two existing overlapping timeslots in the window with same trainer
        Timeslot::factory()->create([
            'start_at' => Carbon::parse('2025-12-26 09:00:00'),
            'end_at' => Carbon::parse('2025-12-26 10:30:00'),
            'trainer_name' => 'Test',
        ]);
        Timeslot::factory()->create([
            'start_at' => Carbon::parse('2025-12-26 10:00:00'),
            'end_at' => Carbon::parse('2025-12-26 11:00:00'),
            'trainer_name' => 'Test',
        ]);

        // New proposal that overlaps both: 10:15-10:45
        $payload = [
            'start_at' => '2025-12-26T10:15:00Z',
            'end_at' => '2025-12-26T10:45:00Z',
            'trainer_name' => 'Test',
        ];

        $this->postJson('/timeslots/check-conflicts', $payload)
            ->assertOk()
            ->assertJson(fn ($json) => $json
                ->has('conflicts.timeslots', 2)
                ->has('conflicts.trainers', 2)
            );
    }

    public function test_cross_midnight_overlap_is_detected(): void
    {
        $this->actingUser();

        // Existing crosses midnight: 23:30 -> 01:00
        Timeslot::factory()->create([
            'start_at' => Carbon::parse('2025-12-27 23:30:00'),
            'end_at' => Carbon::parse('2025-12-28 01:00:00'),
            'trainer_name' => 'Night Coach',
        ]);

        // New: 00:30 -> 02:00 overlaps
        $payload = [
            'start_at' => '2025-12-28T00:30:00Z',
            'end_at' => '2025-12-28T02:00:00Z',
            'trainer_name' => 'Night Coach',
        ];

        $this->postJson('/timeslots/check-conflicts', $payload)
            ->assertOk()
            ->assertJson(fn ($json) => $json
                ->has('conflicts.timeslots', 1)
                ->has('conflicts.trainers', 1)
            );
    }

    public function test_horse_conflict_detects_overlapping_timeslots_with_same_horse(): void
    {
        $this->actingUser();

        $horse = Horse::factory()->create(['name' => 'Spirit', 'is_bookable' => true]);

        $existing = Timeslot::factory()->create([
            'start_at' => Carbon::parse('2025-12-23 10:00:00'),
            'end_at' => Carbon::parse('2025-12-23 11:00:00'),
        ]);
        $existing->horses()->attach($horse->id);

        $payload = [
            'start_at' => '2025-12-23T10:30:00Z',
            'end_at' => '2025-12-23T11:30:00Z',
            'horse_ids' => [$horse->id],
        ];

        $this->postJson('/timeslots/check-conflicts', $payload)
            ->assertOk()
            ->assertJson(fn ($json) => $json
                ->has('conflicts.horses', 1)
                ->has('conflicts.horses.0.horses', 1)
            );
    }
}
