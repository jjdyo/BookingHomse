<?php

use App\Models\Horse;
use App\Models\Timeslot;
use App\Models\TimeslotPreset;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('has new schema elements for presets and color', function () {
    expect(Schema::hasTable('timeslot_presets'))->toBeTrue();
    expect(Schema::hasTable('timeslot_preset_horse'))->toBeTrue();
    // timeslots.color column exists
    expect(Schema::hasColumn('timeslots', 'color'))->toBeTrue();
});

it('can create, update, and delete a timeslot preset with default horses', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $horses = Horse::factory()->count(2)->create([
        'photo_path' => 'horses/sample.jpg',
    ]);

    // Create
    $payload = [
        'preset_title' => 'Private 60',
        'preset_description' => '60 minute private lesson',
        'title' => 'Private Lesson',
        'description' => 'Bring helmet',
        'capacity' => 1,
        'is_group' => false,
        'price' => 60,
        'service_name' => 'Lesson',
        'trainer_name' => 'Jess',
        'location_id' => null,
        'color' => '#3B82F6',
        'horse_ids' => $horses->pluck('id')->all(),
    ];
    $res = $this->post('/dashboard/timeslots/presets', $payload);
    $res->assertRedirect('/dashboard/timeslots/presets');

    $preset = TimeslotPreset::query()->where('preset_title', 'Private 60')->firstOrFail();
    expect($preset->horses)->toHaveCount(2);

    // Update
    $update = $payload;
    $update['price'] = 75;
    $update['_method'] = 'put';
    $res = $this->post("/dashboard/timeslots/presets/{$preset->id}", $update);
    $res->assertRedirect('/dashboard/timeslots/presets');
    $preset->refresh();
    expect((string) $preset->price)->toBe('75.00');

    // Delete
    $res = $this->delete("/dashboard/timeslots/presets/{$preset->id}");
    $res->assertRedirect('/dashboard/timeslots/presets');
    expect(TimeslotPreset::find($preset->id))->toBeNull();
});

it('deploys a preset and serves JSON for prefill including horse image URL when photo_path exists', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $horse = Horse::factory()->create([
        'photo_path' => 'horses/h1.jpg',
    ]);
    $preset = TimeslotPreset::factory()->create([
        'preset_title' => 'Trail 90',
        'title' => 'Trail Ride',
        'color' => '#d4ff00',
    ]);
    $preset->horses()->sync([$horse->id]);

    // Deploy redirect
    $res = $this->get("/dashboard/timeslots/presets/{$preset->id}/deploy");
    $res->assertRedirectContains('/timeslots/create?preset='.$preset->id);

    // JSON prefill
    $json = $this->getJson("/dashboard/timeslots/presets/{$preset->id}");
    $json->assertOk();
    $data = $json->json();
    expect($data['title'])->toBe('Trail Ride');
    expect($data['color'])->toBe('#d4ff00');
    expect($data['horse_ids'])->toBe([$horse->id]);
    // We derive a photo_url from photo_path; allow either absolute or /storage/ URL
    expect($data['horses'][0]['name'])->toBe($horse->name);
    expect($data['horses'][0]['breed'])->toBe($horse->breed);
    expect($data['horses'][0])->toHaveKey('photo_url');
    expect($data['horses'][0]['photo_url'])->not->toBeNull();
});

it('calendar feed includes color on events and defaults when null', function () {
    // With explicit color
    $slot1 = Timeslot::factory()->create([
        'start_at' => now()->addDay(),
        'end_at' => now()->addDay()->addHour(),
        'color' => '#111111',
    ]);
    // Without color
    $slot2 = Timeslot::factory()->create([
        'start_at' => now()->addDays(2),
        'end_at' => now()->addDays(2)->addHour(),
        'color' => null,
    ]);

    $res = $this->get('/timeslots/feed');
    $res->assertOk();
    $events = $res->json();
    $byId = collect($events)->keyBy('id');
    expect($byId[$slot1->id]['backgroundColor'])->toBe('#111111');
    expect($byId[$slot2->id]['backgroundColor'])->toBe('#3B82F6');
});

it('dashboard preset routes are auth protected and return inertia components', function () {
    $this->get('/dashboard/timeslots/presets')->assertRedirectContains('/login');

    $user = User::factory()->create();
    $this->actingAs($user);
    $this->get('/dashboard/timeslots/presets')->assertOk();
    $this->get('/dashboard/timeslots/presets/create')->assertOk();
});
