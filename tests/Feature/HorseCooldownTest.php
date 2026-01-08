<?php

use App\Models\Horse;
use App\Models\Timeslot;
use App\Models\User;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('detects horse cooldown conflict in checkConflicts', function () {
    $horse = Horse::factory()->create([
        'cooldown_duration' => 60,
        'cooldown_unit' => 'minutes',
    ]);

    // Existing timeslot: 10:00 - 11:00
    $existing = Timeslot::factory()->create([
        'start_at' => Carbon::parse('2026-01-08 10:00:00'),
        'end_at' => Carbon::parse('2026-01-08 11:00:00'),
    ]);
    $existing->horses()->attach($horse);

    // New timeslot attempt: 11:30 - 12:30 (within 60 min cooldown of 11:00)
    $response = $this->actingAs($this->user)
        ->postJson('/timeslots/check-conflicts', [
            'start_at' => '2026-01-08 11:30:00',
            'end_at' => '2026-01-08 12:30:00',
            'horse_ids' => [$horse->id],
        ]);

    $response->assertSuccessful();
    $json = $response->json();

    expect($json['conflicts']['cooldowns'])->toHaveCount(1);
    expect($json['conflicts']['cooldowns'][0]['horse']['id'])->toBe($horse->id);
    expect($json['conflicts']['cooldowns'][0]['cooldown_text'])->toBe('60 minutes');
});

it('does not detect conflict outside cooldown period', function () {
    $horse = Horse::factory()->create([
        'cooldown_duration' => 60,
        'cooldown_unit' => 'minutes',
    ]);

    // Existing timeslot: 10:00 - 11:00
    $existing = Timeslot::factory()->create([
        'start_at' => Carbon::parse('2026-01-08 10:00:00'),
        'end_at' => Carbon::parse('2026-01-08 11:00:00'),
    ]);
    $existing->horses()->attach($horse);

    // New timeslot attempt: 12:00 - 13:00 (outside 60 min cooldown of 11:00)
    $response = $this->actingAs($this->user)
        ->postJson('/timeslots/check-conflicts', [
            'start_at' => '2026-01-08 12:00:00',
            'end_at' => '2026-01-08 13:00:00',
            'horse_ids' => [$horse->id],
        ]);

    $response->assertSuccessful();
    $json = $response->json();

    expect($json['conflicts']['cooldowns'])->toBeEmpty();
});

it('detects cooldown conflict before an existing timeslot', function () {
    $horse = Horse::factory()->create([
        'cooldown_duration' => 60,
        'cooldown_unit' => 'minutes',
    ]);

    // Existing timeslot: 12:00 - 13:00
    $existing = Timeslot::factory()->create([
        'start_at' => Carbon::parse('2026-01-08 12:00:00'),
        'end_at' => Carbon::parse('2026-01-08 13:00:00'),
    ]);
    $existing->horses()->attach($horse);

    // New timeslot attempt: 10:30 - 11:30 (cooldown makes it busy until 12:30)
    $response = $this->actingAs($this->user)
        ->postJson('/timeslots/check-conflicts', [
            'start_at' => '2026-01-08 10:30:00',
            'end_at' => '2026-01-08 11:30:00',
            'horse_ids' => [$horse->id],
        ]);

    $response->assertSuccessful();
    $json = $response->json();

    expect($json['conflicts']['cooldowns'])->toHaveCount(1);
});
