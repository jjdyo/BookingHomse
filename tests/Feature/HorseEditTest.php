<?php

namespace Tests\Feature;

use App\Models\Horse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HorseEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_update_horse(): void
    {
        $user = User::factory()->create();
        $horse = Horse::factory()->create([
            'name' => 'Comet',
            'description' => 'Fast horse',
            'breed' => 'Arabian',
            'is_bookable' => true,
            'notes' => 'N/A',
        ]);

        $this->actingAs($user);

        $response = $this->put("/horses/{$horse->id}", [
            'name' => 'Comet II',
            'description' => 'Even faster',
            'breed' => 'Arabian',
            'is_bookable' => false,
            'notes' => 'Updated notes',
        ]);

        $response->assertRedirect(route('dashboard.horses'));

        $this->assertDatabaseHas('horses', [
            'id' => $horse->id,
            'name' => 'Comet II',
            'description' => 'Even faster',
            'breed' => 'Arabian',
            'is_bookable' => false,
            'notes' => 'Updated notes',
        ]);
    }
}
