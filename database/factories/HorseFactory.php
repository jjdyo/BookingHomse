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
            'cooldown_duration' => null,
            'cooldown_unit' => null,
        ];
    }

    /**
     * State for a horse with a cooldown.
     */
    public function withCooldown(?int $duration = null, ?string $unit = null): static
    {
        $unit = $unit ?? $this->faker->randomElement(['minutes', 'hours', 'days']);
        $duration = $duration ?? match ($unit) {
            'minutes' => $this->faker->numberBetween(1, 59),
            'hours' => $this->faker->numberBetween(1, 23),
            'days' => $this->faker->numberBetween(1, 7),
            default => null,
        };

        return $this->state(fn (array $attributes) => [
            'cooldown_duration' => $duration,
            'cooldown_unit' => $unit,
        ]);
    }
}
