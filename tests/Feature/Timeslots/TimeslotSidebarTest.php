<?php

namespace Tests\Feature\Timeslots;

use App\Models\SiteConfig;
use App\Models\Timeslot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimeslotSidebarTest extends TestCase
{
    use RefreshDatabase;

    public function test_sidebar_endpoint_returns_now_happening_and_upcoming_events(): void
    {
        $config = SiteConfig::instance();
        $config->update(['show_event_feed' => true, 'event_feed_lookahead_days' => 7]);

        // 1. "Now Happening" timeslot
        Timeslot::factory()->create([
            'title' => 'Current Event',
            'description' => 'Happening now',
            'start_at' => now()->subMinutes(30),
            'end_at' => now()->addMinutes(30),
        ]);

        // 2. "Upcoming" timeslot
        Timeslot::factory()->create([
            'title' => 'Future Event',
            'description' => 'Starts in an hour',
            'start_at' => now()->addHour(),
            'end_at' => now()->addHours(2),
        ]);

        // 3. Past timeslot (should not appear)
        Timeslot::factory()->create([
            'title' => 'Past Event',
            'start_at' => now()->subHours(2),
            'end_at' => now()->subHour(),
        ]);

        $response = $this->getJson(route('timeslots.sidebar'))
            ->assertOk();

        $response->assertJsonStructure([
            'now_happening' => [
                '*' => ['id', 'title', 'description', 'start_at', 'end_at', 'url'],
            ],
            'upcoming' => [
                '*' => ['id', 'title', 'description', 'start_at', 'end_at', 'url'],
            ],
        ]);

        $json = $response->json();

        $this->assertCount(1, $json['now_happening']);
        $this->assertSame('Current Event', $json['now_happening'][0]['title']);

        $this->assertCount(1, $json['upcoming']);
        $this->assertSame('Future Event', $json['upcoming'][0]['title']);
    }

    public function test_sidebar_is_empty_when_disabled(): void
    {
        $config = SiteConfig::instance();
        $config->update(['show_event_feed' => false]);

        Timeslot::factory()->create([
            'start_at' => now()->subMinutes(30),
            'end_at' => now()->addMinutes(30),
        ]);

        $response = $this->getJson(route('timeslots.sidebar'))
            ->assertOk()
            ->assertJson([
                'now_happening' => [],
                'upcoming' => [],
            ]);
    }

    public function test_sidebar_respects_lookahead_days(): void
    {
        $config = SiteConfig::instance();
        $config->update(['show_event_feed' => true, 'event_feed_lookahead_days' => 1]);

        // Within 1 day
        Timeslot::factory()->create([
            'title' => 'Near Event',
            'start_at' => now()->addHours(12),
            'end_at' => now()->addHours(13),
        ]);

        // Outside 1 day
        Timeslot::factory()->create([
            'title' => 'Far Event',
            'start_at' => now()->addDays(2),
            'end_at' => now()->addDays(2)->addHour(),
        ]);

        $response = $this->getJson(route('timeslots.sidebar'))->assertOk();
        $json = $response->json();

        $this->assertCount(1, $json['upcoming']);
        $this->assertSame('Near Event', $json['upcoming'][0]['title']);
    }

    public function test_sidebar_description_is_truncated(): void
    {
        $longDescription = str_repeat('A', 200);
        Timeslot::factory()->create([
            'title' => 'Long Desc Event',
            'description' => $longDescription,
            'start_at' => now()->subMinutes(10),
            'end_at' => now()->addMinutes(10),
        ]);

        $response = $this->getJson(route('timeslots.sidebar'))->assertOk();
        $json = $response->json();

        $this->assertLessThan(200, mb_strlen($json['now_happening'][0]['description']));
        $this->assertStringEndsWith('...', $json['now_happening'][0]['description']);
    }
}
