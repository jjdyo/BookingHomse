<?php

namespace Database\Factories;

use App\Models\Timeslot;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Timeslot>
 */
class TimeslotFactory extends Factory
{
    protected $model = Timeslot::class;

    public function definition(): array
    {
        $start = Carbon::instance($this->faker->dateTimeBetween('+1 day', '+2 weeks'))->setMinutes(0)->setSeconds(0);
        $end = (clone $start)->addHours($this->faker->randomElement([1, 2]));

        return [
            'title' => $this->faker->randomElement(['Lesson', 'Trail Ride', 'Clinic']).' '.$this->faker->numerify('###'),
            'description' => $this->faker->sentence(10),
            'start_at' => $start,
            'end_at' => $end,
            'capacity' => $this->faker->numberBetween(1, 8),
            'is_group' => $this->faker->boolean(30),
            'price' => $this->faker->randomFloat(2, 25, 200),
            'created_by' => null,
            'trainer_id' => null,
            'trainer_name' => $this->faker->optional()->name(),
            'service_name' => $this->faker->optional()->randomElement(['Beginner Lesson', 'Intermediate Lesson', 'Advanced Lesson', 'Trail']),
            'location_id' => null,
            'color' => $this->faker->optional()->safeHexColor(),
        ];
    }
}
