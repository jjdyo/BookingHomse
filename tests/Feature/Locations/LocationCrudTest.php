<?php

namespace Tests\Feature\Locations;

use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationCrudTest extends TestCase
{
    use RefreshDatabase;

    private function login(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    public function test_index_requires_auth_and_renders(): void
    {
        $this->get('/dashboard/locations')->assertRedirect('/login');

        $this->login();
        $this->get('/dashboard/locations')->assertOk();
    }

    public function test_create_and_store_location_with_valid_data(): void
    {
        $this->login();

        $payload = [
            'name' => 'Main Barn',
            'slug' => 'main-barn',
            'description' => 'Primary riding arena and stables',
            'address' => '123 Farm Road',
            'notes' => 'Parking near the east gate',
            'is_active' => true,
        ];

        $this->post('/locations', $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('locations', [
            'name' => 'Main Barn',
            'slug' => 'main-barn',
            'address' => '123 Farm Road',
            'is_active' => 1,
        ]);
    }

    public function test_validation_unique_name_and_required_description(): void
    {
        $this->login();

        Location::factory()->create(['name' => 'Arena A', 'description' => 'desc']);

        // Missing description
        $this->from('/dashboard/locations/create')->post('/locations', [
            'name' => 'Arena B',
        ])->assertSessionHasErrors(['description']);

        // Duplicate name
        $this->from('/dashboard/locations/create')->post('/locations', [
            'name' => 'Arena A',
            'description' => 'Another',
        ])->assertSessionHasErrors(['name']);
    }

    public function test_update_location(): void
    {
        $this->login();
        $loc = Location::factory()->create(['name' => 'Old Name', 'description' => 'desc']);

        $this->put('/locations/'.$loc->id, [
            'name' => 'New Name',
            'slug' => null,
            'description' => 'Updated',
            'is_active' => false,
        ])->assertRedirect();

        $this->assertDatabaseHas('locations', [
            'id' => $loc->id,
            'name' => 'New Name',
            'is_active' => 0,
        ]);
    }
}
