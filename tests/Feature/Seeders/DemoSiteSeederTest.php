<?php

namespace Tests\Feature\Seeders;

use App\Models\Horse;
use App\Models\Location;
use App\Models\Timeslot;
use App\Models\TimeslotPreset;
use App\Models\Trainer;
use Database\Seeders\DemoSite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemoSiteSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_site_seeder_populates_counts_and_multiday_and_photos(): void
    {
        // Run only the demo seeder for determinism
        $this->seed(DemoSite::class);

        $this->assertGreaterThanOrEqual(20, Trainer::count());
        $this->assertGreaterThanOrEqual(5, Location::count());
        $this->assertGreaterThanOrEqual(10, Horse::count());
        $this->assertGreaterThanOrEqual(30, Timeslot::count());

        // At least 3 multi-day timeslots (strictly more than 2 days span)
        $multiDay = Timeslot::query()->get()->filter(function (Timeslot $t) {
            return $t->end_at->greaterThan($t->start_at->copy()->addDays(2));
        });
        $this->assertGreaterThanOrEqual(3, $multiDay->count());

        // Presets seeded (>=6)
        $this->assertGreaterThanOrEqual(6, TimeslotPreset::count());

        // If demo images exist in public/images, ensure at least some photo files exist on disk
        $imagesExist = file_exists(public_path('images/Horse1.jpg'))
            || file_exists(public_path('images/Trainer1.jpg'))
            || file_exists(public_path('images/Location1.jpg'));

        if ($imagesExist) {
            $someTrainer = Trainer::whereNotNull('photo_path')->first();
            $someHorse = Horse::whereNotNull('photo_path')->first();
            $someLocation = Location::whereNotNull('photo_path')->first();

            $this->assertNotNull($someTrainer);
            $this->assertNotNull($someHorse);
            $this->assertNotNull($someLocation);

            if ($someTrainer) {
                $this->assertTrue(Storage::disk('public')->exists($someTrainer->photo_path));
            }
            if ($someHorse) {
                $this->assertTrue(Storage::disk('public')->exists($someHorse->photo_path));
            }
            if ($someLocation) {
                $this->assertTrue(Storage::disk('public')->exists($someLocation->photo_path));
            }
        }

        // New guarantees: every timeslot has all core fields and attached entities for demo purposes
        foreach (Timeslot::all() as $t) {
            $this->assertNotNull($t->title);
            $this->assertNotNull($t->description);
            $this->assertNotNull($t->start_at);
            $this->assertNotNull($t->end_at);
            $this->assertNotNull($t->capacity);
            $this->assertNotNull($t->price);
            $this->assertNotNull($t->service_name);
            $this->assertNotNull($t->location_id);

            $trainerCount = $t->trainers()->count();
            $horseCount = $t->horses()->count();
            $this->assertGreaterThanOrEqual(1, $trainerCount);
            $this->assertLessThanOrEqual(5, $trainerCount);
            $this->assertGreaterThanOrEqual(1, $horseCount);
            $this->assertLessThanOrEqual(5, $horseCount);
        }
    }
}
