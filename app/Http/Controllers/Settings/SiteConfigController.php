<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\SiteConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SiteConfigController extends Controller
{
    public function edit()
    {
        $config = SiteConfig::instance();

        return Inertia::render('dashboard/settings/SiteConfiguration', [
            'config' => [
                'site_name' => $config->site_name,
                'booking_open_time' => $config->booking_open_time,
                'booking_close_time' => $config->booking_close_time,
                'logo_url' => $config->logo_url,
                'warn_overbook_trainers' => (bool) $config->warn_overbook_trainers,
                'warn_overbook_horses' => (bool) $config->warn_overbook_horses,
                'warn_overbook_timeslots' => (bool) $config->warn_overbook_timeslots,
            ],
        ]);
    }

    public function update(Request $request)
    {
        Log::info('SiteConfig update: request started', [
            'user_id' => optional($request->user())->id,
            'method' => $request->method(),
            'has_file_logo' => $request->hasFile('logo'),
            'content_type' => $request->header('Content-Type'),
            // Log only the keys present (not values) to confirm transport layer behavior
            'input_keys' => array_keys($request->except(['logo'])),
        ]);
        // Pre-normalize inputs to avoid false "required" failures and accept HH:MM:SS
        $siteName = trim((string) $request->input('site_name', ''));
        $open = trim((string) $request->input('booking_open_time', ''));
        $close = trim((string) $request->input('booking_close_time', ''));

        // Convert HH:MM:SS -> HH:MM for validation
        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $open)) {
            $open = substr($open, 0, 5);
        }
        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $close)) {
            $close = substr($close, 0, 5);
        }

        // Fallback to existing config values if empty strings were sent
        $config = SiteConfig::instance();
        if ($siteName === '') {
            $siteName = $config->site_name ?? 'Booking Homse';
        }
        if ($open === '') {
            $open = substr((string) ($config->booking_open_time ?? '09:00:00'), 0, 5);
        }
        if ($close === '') {
            $close = substr((string) ($config->booking_close_time ?? '19:00:00'), 0, 5);
        }

        // Merge normalized values back for validation
        $request->merge([
            'site_name' => $siteName,
            'booking_open_time' => $open,
            'booking_close_time' => $close,
        ]);

        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:255'],
            'booking_open_time' => ['required', 'date_format:H:i'],
            'booking_close_time' => ['required', 'date_format:H:i', 'after:booking_open_time'],
            // Accept common image mime types including SVG; avoid Laravel's image rule which rejects SVG
            'logo' => ['nullable', 'mimetypes:image/png,image/jpeg,image/webp,image/svg+xml', 'max:2048'],
            'warn_overbook_trainers' => ['nullable', 'boolean'],
            'warn_overbook_horses' => ['nullable', 'boolean'],
            'warn_overbook_timeslots' => ['nullable', 'boolean'],
        ]);

        $original = [
            'site_name' => $config->site_name,
            'booking_open_time' => $config->booking_open_time,
            'booking_close_time' => $config->booking_close_time,
            'logo_path' => $config->logo_path,
        ];

        $config->site_name = $data['site_name'];
        $config->booking_open_time = $data['booking_open_time'].':00';
        $config->booking_close_time = $data['booking_close_time'].':00';

        // Booleans: if missing from request, treat as false (HTML unchecked checkboxes are omitted)
        $config->warn_overbook_trainers = (bool) ($request->boolean('warn_overbook_trainers'));
        $config->warn_overbook_horses = (bool) ($request->boolean('warn_overbook_horses'));
        $config->warn_overbook_timeslots = (bool) ($request->boolean('warn_overbook_timeslots'));

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');
            // Optionally delete old logo file
            if ($config->logo_path && Storage::disk('public')->exists($config->logo_path)) {
                Storage::disk('public')->delete($config->logo_path);
            }
            $config->logo_path = $path;
        }
        $config->save();

        Log::info('SiteConfig update: persisted successfully', [
            'user_id' => optional($request->user())->id,
            'updated' => [
                'site_name' => $config->site_name,
                'booking_open_time' => $config->booking_open_time,
                'booking_close_time' => $config->booking_close_time,
                'logo_path' => $config->logo_path,
                'warn_overbook_trainers' => (bool) $config->warn_overbook_trainers,
                'warn_overbook_horses' => (bool) $config->warn_overbook_horses,
                'warn_overbook_timeslots' => (bool) $config->warn_overbook_timeslots,
            ],
            'original' => $original,
        ]);

        return redirect()->back()->with('success', 'Settings updated.');
    }

    // Public JSON for frontend widgets (calendars, header, etc.)
    public function publicSettings()
    {
        $config = SiteConfig::instance();

        return response()->json([
            'site_name' => $config->site_name,
            'booking_open_time' => $config->booking_open_time, // HH:MM:SS
            'booking_close_time' => $config->booking_close_time, // HH:MM:SS
            'logo_url' => $config->logo_url,
            'warn_overbook_trainers' => (bool) $config->warn_overbook_trainers,
            'warn_overbook_horses' => (bool) $config->warn_overbook_horses,
            'warn_overbook_timeslots' => (bool) $config->warn_overbook_timeslots,
        ]);
    }
}
