<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHorseRequest;
use App\Http\Requests\UpdateHorseRequest;
use App\Models\Horse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class HorseController extends Controller
{
    public function index(): Response
    {
        $horses = Horse::query()
            ->ordered()
            ->get(['id', 'name', 'description', 'breed', 'is_bookable', 'notes', 'photo_path', 'cooldown_duration', 'cooldown_unit', 'created_at', 'updated_at'])
            ->map(function (Horse $h) {
                return [
                    'id' => $h->id,
                    'name' => $h->name,
                    'description' => $h->description,
                    'breed' => $h->breed,
                    'is_bookable' => (bool) $h->is_bookable,
                    'notes' => $h->notes,
                    'photo_path' => $h->photo_path,
                    'photo_url' => $h->photo_url,
                    'cooldown_duration' => $h->cooldown_duration,
                    'cooldown_unit' => $h->cooldown_unit,
                    'created_at' => $h->created_at,
                    'updated_at' => $h->updated_at,
                ];
            });

        return Inertia::render('dashboard/horses/HorsesIndex', [
            'horses' => $horses,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('dashboard/horses/CreateHorse');
    }

    public function store(StoreHorseRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Prefer chosen library media when provided; otherwise accept uploaded file
        $photoPath = $data['photo_path'] ?? null;
        if (! $photoPath && $request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('horses', 'public');
        }

        Horse::create([
            'name' => $data['name'],
            'description' => $data['description'],
            'breed' => $data['breed'] ?? null,
            'is_bookable' => (bool) ($data['is_bookable'] ?? true),
            'notes' => $data['notes'] ?? null,
            'photo_path' => $photoPath,
            'cooldown_duration' => $data['cooldown_duration'] ?? null,
            'cooldown_unit' => $data['cooldown_unit'] ?? null,
        ]);

        return redirect()->route('dashboard.horses')->with('success', 'Horse created.');
    }

    public function edit(Horse $horse): Response
    {
        return Inertia::render('dashboard/horses/EditHorse', [
            'horse' => $horse->only(['id', 'name', 'description', 'breed', 'is_bookable', 'notes', 'photo_path', 'cooldown_duration', 'cooldown_unit']) + [
                'photo_url' => $horse->photo_url,
            ],
        ]);
    }

    public function update(UpdateHorseRequest $request, Horse $horse): RedirectResponse
    {
        $data = $request->validated();

        $update = [
            'name' => $data['name'],
            'description' => $data['description'],
            'breed' => $data['breed'] ?? null,
            'is_bookable' => (bool) ($data['is_bookable'] ?? false),
            'notes' => $data['notes'] ?? null,
            'cooldown_duration' => $data['cooldown_duration'] ?? null,
            'cooldown_unit' => $data['cooldown_unit'] ?? null,
        ];

        if ($request->hasFile('photo')) {
            if ($horse->photo_path) {
                Storage::disk('public')->delete($horse->photo_path);
            }
            $update['photo_path'] = $request->file('photo')->store('horses', 'public');
        } elseif (! empty($data['photo_path'])) {
            $update['photo_path'] = $data['photo_path'];
        }

        $horse->update($update);

        return redirect()->route('dashboard.horses')->with('success', 'Horse updated.');
    }

    /**
     * Lightweight typeahead search for horses.
     * Returns minimal details for bookable horses matching the query.
     */
    public function search(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $limit = (int) $request->query('limit', 8);
        $limit = max(1, min($limit, 25));

        $query = Horse::query()->where('is_bookable', true);
        if ($q !== '') {
            $query->where('name', 'like', "%{$q}%");
        }

        $horses = $query->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'breed', 'photo_path'])
            ->map(function (Horse $h) {
                return [
                    'id' => $h->id,
                    'name' => $h->name,
                    'breed' => $h->breed,
                    'photo_url' => $h->photo_path ? Storage::disk('public')->url($h->photo_path) : null,
                ];
            });

        return response()->json($horses->values());
    }
}
