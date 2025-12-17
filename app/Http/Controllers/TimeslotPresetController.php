<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTimeslotPresetRequest;
use App\Http\Requests\UpdateTimeslotPresetRequest;
use App\Models\TimeslotPreset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema as DBSchema;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class TimeslotPresetController extends Controller
{
    public function index(): Response
    {
        // Eager-load horses for display; keep payload small by selecting only necessary fields
        $presets = TimeslotPreset::query()
            ->with(['horses:id,name'])
            ->orderBy('preset_title')
            ->get()
            ->map(function (TimeslotPreset $p) {
                return [
                    'id' => $p->id,
                    'preset_title' => $p->preset_title,
                    'preset_description' => $p->preset_description,
                    'title' => $p->title,
                    'description' => $p->description,
                    'capacity' => $p->capacity,
                    'price' => $p->price,
                    'service_name' => $p->service_name,
                    'trainer_name' => $p->trainer_name,
                    'color' => $p->color,
                    'horses' => $p->horses->map(fn ($h) => ['id' => $h->id, 'name' => $h->name])->all(),
                ];
            });

        return Inertia::render('dashboard/timeslots/PresetsIndex', [
            'presets' => $presets,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('dashboard/timeslots/CreatePreset');
    }

    public function store(StoreTimeslotPresetRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $horseIds = (array) ($data['horse_ids'] ?? []);
        unset($data['horse_ids']);

        // Normalize nullable -> defaults to satisfy NOT NULL constraints (e.g., SQLite)
        $data['capacity'] = $data['capacity'] ?? 1;
        $data['is_group'] = (bool) ($data['is_group'] ?? false);
        $data['price'] = $data['price'] ?? 0;

        $preset = TimeslotPreset::create($data);
        if (! empty($horseIds)) {
            $preset->horses()->sync(array_values(array_unique($horseIds)));
        }

        return redirect()->route('dashboard.timeslots.presets')->with('success', 'Preset created.');
    }

    public function edit(TimeslotPreset $preset): Response
    {
        $preset->load('horses');
        // Build a stable, explicit payload to ensure horses (with photo_path) are present client-side
        $payload = [
            'id' => $preset->id,
            'preset_title' => $preset->preset_title,
            'preset_description' => $preset->preset_description,
            'title' => $preset->title,
            'description' => $preset->description,
            'capacity' => $preset->capacity,
            'is_group' => (bool) $preset->is_group,
            'price' => $preset->price,
            'service_name' => $preset->service_name,
            'trainer_id' => $preset->trainer_id,
            'trainer_name' => $preset->trainer_name,
            'location_id' => $preset->location_id,
            'color' => $preset->color,
            'horses' => $preset->horses->map(fn ($h) => [
                'id' => $h->id,
                'name' => $h->name,
                // Keep raw photo_path; the client maps to /storage when needed
                'photo_path' => $h->photo_path,
                'breed' => $h->breed,
            ])->all(),
        ];

        return Inertia::render('dashboard/timeslots/EditPreset', [
            'preset' => $payload,
            'horse_ids' => $preset->horses->pluck('id'),
        ]);
    }

    public function update(UpdateTimeslotPresetRequest $request, TimeslotPreset $preset): RedirectResponse
    {
        $data = $request->validated();
        $horseIds = (array) ($data['horse_ids'] ?? []);
        unset($data['horse_ids']);

        // Normalize nullable -> defaults
        $data['capacity'] = $data['capacity'] ?? 1;
        $data['is_group'] = (bool) ($data['is_group'] ?? false);
        $data['price'] = $data['price'] ?? 0;

        $preset->update($data);
        $preset->horses()->sync(array_values(array_unique($horseIds)));

        return redirect()->route('dashboard.timeslots.presets')->with('success', 'Preset updated.');
    }

    public function destroy(TimeslotPreset $preset): RedirectResponse
    {
        $preset->delete();

        return redirect()->route('dashboard.timeslots.presets')->with('success', 'Preset deleted.');
    }

    // JSON payload for prefill in CreateTimeslot
    public function show(Request $request, TimeslotPreset $preset)
    {
        if ($request->expectsJson()) {
            try {
                // Prefer photo_path + breed when available
                $hasPhotoPath = DBSchema::hasColumn('horses', 'photo_path');
                $hasBreed = DBSchema::hasColumn('horses', 'breed');
                $columns = ['id', 'name'];
                if ($hasBreed) {
                    $columns[] = 'breed';
                }
                if ($hasPhotoPath) {
                    $columns[] = 'photo_path';
                }
                $preset->load(['horses:'.implode(',', $columns)]);

                return [
                    'id' => $preset->id,
                    'title' => $preset->title,
                    'description' => $preset->description,
                    'capacity' => $preset->capacity,
                    'is_group' => (bool) $preset->is_group,
                    'price' => $preset->price,
                    'service_name' => $preset->service_name,
                    'trainer_id' => $preset->trainer_id,
                    'trainer_name' => $preset->trainer_name,
                    'location_id' => $preset->location_id,
                    'color' => $preset->color,
                    'horse_ids' => $preset->horses->pluck('id')->all(),
                    'horses' => $preset->horses->map(function ($h) use ($hasPhotoPath, $hasBreed) {
                        return [
                            'id' => $h->id,
                            'name' => $h->name,
                            'photo_url' => $hasPhotoPath && $h->photo_path ? Storage::url($h->photo_path) : null,
                            'breed' => $hasBreed ? $h->breed : null,
                        ];
                    })->all(),
                ];
            } catch (\Throwable $e) {
                Log::error('Preset JSON show failed, falling back to minimal horses', [
                    'preset_id' => $preset->id,
                    'error' => $e->getMessage(),
                ]);
                // Fallback minimal response
                $preset->load(['horses:id,name']);

                return [
                    'id' => $preset->id,
                    'title' => $preset->title,
                    'description' => $preset->description,
                    'capacity' => $preset->capacity,
                    'is_group' => (bool) $preset->is_group,
                    'price' => $preset->price,
                    'service_name' => $preset->service_name,
                    'trainer_id' => $preset->trainer_id,
                    'trainer_name' => $preset->trainer_name,
                    'location_id' => $preset->location_id,
                    'color' => $preset->color,
                    'horse_ids' => $preset->horses->pluck('id')->all(),
                    'horses' => $preset->horses->map(fn ($h) => [
                        'id' => $h->id,
                        'name' => $h->name,
                        'photo_url' => null,
                        'breed' => null,
                    ])->all(),
                ];
            }
        }

        return redirect()->route('dashboard.timeslots.presets');
    }

    // Redirect to create timeslot with ?preset=ID
    public function deploy(TimeslotPreset $preset): RedirectResponse
    {
        return redirect()->to(url('/timeslots/create').'?preset='.$preset->id);
    }
}
