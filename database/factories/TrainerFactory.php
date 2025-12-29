<?php

namespace Database\Factories;

use App\Models\Trainer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Trainer>
 */
class TrainerFactory extends Factory
{
    protected $model = Trainer::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'title' => $this->faker->randomElement([
                'Head Trainer',
                'Assistant Trainer',
                'Riding Instructor',
                'Dressage Coach',
                'Jumping Coach',
            ]),
            'bio' => $this->faker->paragraphs(2, true),
            // Keep null by default to avoid broken file references during seeding/tests;
            // upload handling should populate this with a real path on create.
            'photo_path' => null,
            'is_bookable' => true,
        ];
    }

    /**
     * State: mark trainer as not bookable.
     */
    public function unbookable(): self
    {
        return $this->state(fn () => ['is_bookable' => false]);
    }
}
