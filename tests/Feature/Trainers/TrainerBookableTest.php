<?php

use App\Models\Trainer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('defaults trainers to bookable on create and can be set false', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create with default (no is_bookable passed)
    $res = $this->post('/trainers', [
        'name' => 'Sam Coach',
        'title' => 'Riding Instructor',
        'bio' => 'Experienced coach',
    ]);
    $res->assertRedirect();

    $trainer = Trainer::firstOrFail();
    expect($trainer->is_bookable)->toBeTrue();

    // Update to set non-bookable
    $res2 = $this->put("/trainers/{$trainer->id}", [
        'name' => 'Sam Coach',
        'title' => 'Riding Instructor',
        'bio' => 'Experienced coach',
        'is_bookable' => false,
    ]);
    $res2->assertRedirect();

    $trainer->refresh();
    expect($trainer->is_bookable)->toBeFalse();
});

it('search excludes non-bookable trainers', function () {
    // Create data
    $this->actingAs(User::factory()->create());
    Trainer::factory()->create(['name' => 'Alex Pro', 'is_bookable' => true]);
    Trainer::factory()->unbookable()->create(['name' => 'Alex Hidden']);

    $res = $this->getJson('/trainers/search?q=Alex&limit=10');
    $res->assertSuccessful();
    $names = collect($res->json())->pluck('name');

    expect($names)->toContain('Alex Pro')
        ->and($names)->not->toContain('Alex Hidden');
});
