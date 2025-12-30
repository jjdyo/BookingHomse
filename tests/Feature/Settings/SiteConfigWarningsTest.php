<?php

namespace Tests\Feature\Settings;

use App\Models\SiteConfig;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteConfigWarningsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_warning_flags_and_public_endpoint_exposes_them(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Ensure singleton exists
        $config = SiteConfig::instance();

        // Update: turn off trainers/horses, keep timeslots on
        $resp = $this->post('/dashboard/settings/site', [
            '_method' => 'PATCH',
            'site_name' => 'Booking Homse',
            'booking_open_time' => '09:00',
            'booking_close_time' => '19:00',
            'event_feed_lookahead_days' => 7,
            // booleans: send only timeslots to keep on
            'warn_overbook_timeslots' => 1,
        ]);
        $resp->assertRedirect();

        $config->refresh();
        $this->assertFalse((bool) $config->warn_overbook_trainers);
        $this->assertFalse((bool) $config->warn_overbook_horses);
        $this->assertTrue((bool) $config->warn_overbook_timeslots);

        // Public JSON should include the flags
        $json = $this->getJson('/settings/public')
            ->assertOk()
            ->json();

        $this->assertArrayHasKey('warn_overbook_trainers', $json);
        $this->assertArrayHasKey('warn_overbook_horses', $json);
        $this->assertArrayHasKey('warn_overbook_timeslots', $json);
        $this->assertFalse($json['warn_overbook_trainers']);
        $this->assertFalse($json['warn_overbook_horses']);
        $this->assertTrue($json['warn_overbook_timeslots']);
    }
}
