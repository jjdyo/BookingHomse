<?php

namespace Database\Factories;

use App\Models\TimeslotPreset;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TimeslotPreset>
 */
class TimeslotPresetFactory extends Factory
{
    protected $model = TimeslotPreset::class;

    public function definition(): array
    {
        return [
            'preset_title' => $this->faker->sentence(3),
            'preset_description' => $this->faker->optional()->sentence(8),
            'title' => $this->faker->randomElement(['Lesson', 'Trail Ride', 'Clinic']).' '.$this->faker->numerify('###'),
            'description' => $this->faker->optional()->sentence(10),
            'capacity' => $this->faker->numberBetween(1, 8),
            'is_group' => $this->faker->boolean(30),
            'price' => $this->faker->randomFloat(2, 25, 200),
            'trainer_id' => null,
            'trainer_name' => $this->faker->optional()->name(),
            'service_name' => $this->faker->optional()->randomElement(['Beginner Lesson', 'Intermediate Lesson', 'Advanced Lesson', 'Trail']),
            'location_id' => null,
            'color' => $this->faker->optional()->hexColor(),
        ];
    }
}
