<?php

namespace Database\Factories;

use App\Models\SiteConfig;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SiteConfig>
 */
class SiteConfigFactory extends Factory
{
    protected $model = SiteConfig::class;

    public function definition(): array
    {
        return [
            'site_name' => $this->faker->company().' Stables',
            'booking_open_time' => '09:00:00',
            'booking_close_time' => '19:00:00',
            'logo_path' => null,
        ];
    }
}
