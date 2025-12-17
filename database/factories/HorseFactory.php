<?php

namespace Database\Factories;

use App\Models\Horse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Horse>
 */
class HorseFactory extends Factory
{
    protected $model = Horse::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->firstName().' '.$this->faker->randomElement(['Star', 'Bolt', 'Comet', 'Spirit']),
            'description' => $this->faker->optional()->sentence(8),
            'breed' => $this->faker->randomElement(['Arabian', 'Quarter Horse', 'Thoroughbred', 'Mustang', 'Morgan']),
            'is_bookable' => true,
            'notes' => $this->faker->optional()->paragraph(),
        ];
    }
}
