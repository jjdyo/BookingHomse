<?php

namespace Database\Factories;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
{
    protected $model = Location::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->company().' Barn';

        return [
            'name' => $name,
            'slug' => $this->faker->boolean(80) ? Str::slug($name).'-'.Str::random(4) : null,
            'description' => $this->faker->paragraph(2),
            'address' => $this->faker->optional()->address(),
            'notes' => $this->faker->optional()->sentences(2, true),
            'photo_path' => null,
            'is_active' => true,
        ];
    }
}
