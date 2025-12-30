<?php

namespace Tests\Feature\Settings;

use App\Models\SiteConfig;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SiteConfigTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure the singleton row exists
        SiteConfig::instance();
    }

    public function test_guest_cannot_access_edit_or_update(): void
    {
        $this->get('/dashboard/settings/site')->assertRedirect('/login');
        $this->patch('/dashboard/settings/site', [
            'site_name' => 'New Name',
            'booking_open_time' => '08:00',
            'booking_close_time' => '18:00',
        ])->assertRedirect('/login');
    }

    public function test_authenticated_user_can_update_basic_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch('/dashboard/settings/site', [
            'site_name' => 'Homse Ranch',
            'booking_open_time' => '08:30',
            'booking_close_time' => '17:45',
            'event_feed_lookahead_days' => 7,
        ]);

        $response->assertRedirect();

        $config = SiteConfig::instance()->fresh();
        $this->assertSame('Homse Ranch', $config->site_name);
        $this->assertSame('08:30:00', $config->booking_open_time);
        $this->assertSame('17:45:00', $config->booking_close_time);
    }

    public function test_formdata_style_post_with_method_spoof_updates_config(): void
    {
        $user = User::factory()->create();

        // Simulate Inertia router.patch with forceFormData: true which sends POST + _method=PATCH
        $response = $this->actingAs($user)->post('/dashboard/settings/site', [
            '_method' => 'PATCH',
            'site_name' => 'FormData Name',
            'booking_open_time' => '06:15',
            'booking_close_time' => '20:45',
            'event_feed_lookahead_days' => 14,
        ]);

        $response->assertRedirect();

        $config = SiteConfig::instance()->fresh();
        $this->assertSame('FormData Name', $config->site_name);
        $this->assertSame('06:15:00', $config->booking_open_time);
        $this->assertSame('20:45:00', $config->booking_close_time);
    }

    public function test_logo_upload_is_stored_and_old_logo_is_deleted(): void
    {
        $user = User::factory()->create();
        Storage::fake('public');

        $config = SiteConfig::instance();
        // Seed an existing logo to verify deletion on replace
        $config->logo_path = 'logos/old.png';
        Storage::disk('public')->put($config->logo_path, 'old');
        $config->save();

        $file = UploadedFile::fake()->image('logo.png', 120, 120);

        $response = $this->actingAs($user)->patch('/dashboard/settings/site', [
            'site_name' => 'Logo Test',
            'booking_open_time' => '09:00',
            'booking_close_time' => '18:00',
            'event_feed_lookahead_days' => 7,
            'logo' => $file,
        ]);

        $response->assertRedirect();

        $config = $config->fresh();
        $this->assertNotNull($config->logo_path);
        Storage::disk('public')->assertExists($config->logo_path);
        // Old file should be removed
        Storage::disk('public')->assertMissing('logos/old.png');
    }

    public function test_public_settings_endpoint_returns_expected_shape(): void
    {
        $config = SiteConfig::instance();
        $config->site_name = 'Public Name';
        $config->booking_open_time = '07:00:00';
        $config->booking_close_time = '16:00:00';
        $config->logo_path = 'logos/example.png';
        $config->save();

        $res = $this->getJson('/settings/public')
            ->assertOk()
            ->json();

        $this->assertSame('Public Name', $res['site_name']);
        $this->assertSame('07:00:00', $res['booking_open_time']);
        $this->assertSame('16:00:00', $res['booking_close_time']);
        $this->assertNotEmpty($res['logo_url']);
    }
}
