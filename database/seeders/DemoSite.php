<?php

namespace Database\Seeders;

use App\Models\Horse;
use App\Models\Location;
use App\Models\Timeslot;
use App\Models\TimeslotPreset;
use App\Models\Trainer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class DemoSite extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Neutral, high-contrast friendly palette (aiming for readability on light and dark surfaces)
        $palette = [
            '#2563EB', // blue-600
            '#1D4ED8', // blue-700
            '#7C3AED', // violet-600
            '#0F766E', // teal-700
            '#059669', // emerald-600
            '#475569', // slate-600 (neutral)
        ];
        // Copy demo images (if present) into public storage and collect relative paths
        $trainerPhotos = $this->copyDemoImages('trainers', ['Trainer1.jpg', 'Trainer2.jpg', 'Trainer3.jpg']);
        $horsePhotos = $this->copyDemoImages('horses', ['Horse1.jpg', 'Horse2.jpg', 'Horse3.jpg']);
        $locationPhotos = $this->copyDemoImages('locations', ['Location1.jpg', 'Location2.jpg', 'Location3.jpg']);

        // Trainers: 20
        $trainers = Trainer::factory()->count(20)->create()->each(function (Trainer $t) use ($trainerPhotos) {
            if (! empty($trainerPhotos)) {
                $t->update(['photo_path' => collect($trainerPhotos)->random()]);
            }
        });

        // Locations: 5
        $locations = Location::factory()->count(5)->create()->each(function (Location $l) use ($locationPhotos) {
            if (! empty($locationPhotos)) {
                $l->update(['photo_path' => collect($locationPhotos)->random()]);
            }
        });

        // Horses: 10
        $horses = Horse::factory()->count(10)->create()->each(function (Horse $h) use ($horsePhotos) {
            if (! empty($horsePhotos)) {
                $h->update(['photo_path' => collect($horsePhotos)->random()]);
            }
        });

        // Timeslots: ~30 across past/current/future months
        $now = Carbon::now();
        $ranges = [
            [$now->copy()->subMonth()->startOfMonth(), $now->copy()->subMonth()->endOfMonth()],
            [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            [$now->copy()->addMonth()->startOfMonth(), $now->copy()->addMonth()->endOfMonth()],
        ];

        $totalSlots = 30;

        // Make ~10% (or at least 3) of timeslots multi‑day (3 days long)
        $multiCount = max(3, (int) ceil($totalSlots * 0.10));
        $multiIndices = collect(range(0, $totalSlots - 1))
            ->shuffle()
            ->take($multiCount)
            ->merge([0, 1, 2]) // guarantee at least 3 multi‑day slots deterministically
            ->unique()
            ->all();

        // Seed three guaranteed multi-day slots up front
        for ($i = 0; $i < 3; $i++) {
            $trainer = $trainers->random();
            $location = $locations->random();
            $start = $now->copy()->addDays($i + 1)->hour(9)->minute(0)->second(0);
            $end = $start->copy()->addDays(3)->addHour();
            $slot = Timeslot::create([
                'title' => 'Multi-day Demo '.($i + 1),
                'description' => 'Three day demo slot',
                'start_at' => $start,
                'end_at' => $end,
                'capacity' => fake()->numberBetween(1, 8),
                'is_group' => fake()->boolean(30),
                'price' => fake()->randomFloat(2, 25, 200),
                'trainer_name' => $trainer->name,
                'service_name' => fake()->optional()->randomElement(['Beginner', 'Intermediate', 'Advanced', 'Trail']),
                'location_id' => $location->id,
                'color' => fake()->optional()->randomElement($palette),
            ]);
            // attach some horses
            $attachCount = fake()->numberBetween(0, 3);
            if ($attachCount > 0) {
                $slot->horses()->sync($horses->random($attachCount)->pluck('id')->values()->all());
            }
        }

        for ($i = 0; $i < $totalSlots; $i++) {
            $range = $ranges[$i % 3];
            $start = Carbon::instance(fake()->dateTimeBetween($range[0], $range[1]))
                ->minute(0)->second(0);
            // Determine if this slot should be multi‑day
            if (in_array($i, $multiIndices, true)) {
                // 3-day long slot (+1h to ensure spans across 3 calendar days in all TZs)
                $end = $start->copy()->addDays(3)->addHour();
            } else {
                $durationHours = fake()->randomElement([1, 1, 2]);
                $end = $start->copy()->addHours($durationHours);
            }

            $trainer = $trainers->random();
            $location = $locations->random();

            $slot = Timeslot::create([
                'title' => fake()->randomElement(['Lesson', 'Trail Ride', 'Clinic', 'Open Arena']).' '.fake()->numerify('###'),
                'description' => fake()->sentence(10),
                'start_at' => $start,
                'end_at' => $end,
                'capacity' => fake()->numberBetween(1, 8),
                'is_group' => fake()->boolean(30),
                'price' => fake()->randomFloat(2, 25, 200),
                'trainer_name' => $trainer->name,
                'service_name' => fake()->optional()->randomElement(['Beginner', 'Intermediate', 'Advanced', 'Trail']),
                'location_id' => $location->id,
                'color' => fake()->optional()->randomElement($palette),
            ]);

            // Attach 0-3 horses to the timeslot
            $attachCount = fake()->numberBetween(0, 3);
            if ($attachCount > 0) {
                $slot->horses()->sync($horses->random($attachCount)->pluck('id')->values()->all());
            }
        }

        // Final safeguard: ensure at least 3 multi‑day timeslots exist
        $ensure = Timeslot::query()->orderBy('id')->limit(3)->get();
        foreach ($ensure as $t) {
            $t->update([
                'end_at' => $t->start_at->copy()->addDays(3)->addHour(),
            ]);
        }

        // Seed Timeslot Presets (6–10) with variety
        $presetCount = fake()->numberBetween(6, 10);
        for ($i = 0; $i < $presetCount; $i++) {
            $maybeTrainer = $trainers->random();
            $maybeLocation = $locations->random();
            TimeslotPreset::factory()->create([
                'preset_title' => fake()->randomElement(['Private Lesson', 'Group Clinic', 'Trail Ride', 'Open Arena']).' Preset',
                'preset_description' => fake()->optional()->sentence(10),
                'capacity' => fake()->numberBetween(1, 8),
                'is_group' => fake()->boolean(30),
                'price' => fake()->randomFloat(2, 25, 200),
                'trainer_id' => null,
                'trainer_name' => fake()->boolean(60) ? $maybeTrainer->name : null,
                'service_name' => fake()->optional()->randomElement(['Beginner', 'Intermediate', 'Advanced', 'Trail']),
                'location_id' => fake()->boolean(60) ? $maybeLocation->id : null,
                'color' => fake()->optional()->randomElement($palette),
            ]);
        }
    }

    /**
     * Copy demo images from public/images into the public disk under the given directory.
     * Returns an array of relative paths (e.g., "horses/Horse1.jpg").
     */
    private function copyDemoImages(string $targetDir, array $filenames): array
    {
        $stored = [];
        foreach ($filenames as $name) {
            $source = public_path('images/'.$name);
            if (! file_exists($source)) {
                continue;
            }
            $destRelative = $targetDir.'/'.$name;
            // idempotent: if already exists, do not overwrite
            if (! Storage::disk('public')->exists($destRelative)) {
                try {
                    $contents = file_get_contents($source);
                    if ($contents !== false) {
                        Storage::disk('public')->put($destRelative, $contents);
                    }
                } catch (\Throwable $e) {
                    // swallow and skip this file
                }
            }
            if (Storage::disk('public')->exists($destRelative)) {
                $stored[] = $destRelative;
            }
        }

        return $stored;
    }
}
