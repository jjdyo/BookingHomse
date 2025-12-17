<?php

namespace Tests\Feature\Horses;

use App\Models\Horse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HorseSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_search_bookable_horses(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Horse::factory()->create(['name' => 'Star Runner', 'is_bookable' => true]);
        Horse::factory()->create(['name' => 'Morning Glory', 'is_bookable' => true]);
        Horse::factory()->create(['name' => 'Retired Champ', 'is_bookable' => false]);

        $this->getJson('/horses/search?q=Star')
            ->assertOk()
            ->assertJson(fn ($json) => $json
                ->has(1)
                ->first(fn ($item) => $item
                    ->where('name', 'Star Runner')
                    ->etc()
                )
            );
    }
}
