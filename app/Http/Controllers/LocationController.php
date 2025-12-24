<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLocationRequest;
use App\Http\Requests\UpdateLocationRequest;
use App\Models\Location;
use App\Models\Timeslot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class LocationController extends Controller
{
    public function index(): Response
    {
        $locations = Location::query()
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'description', 'address', 'notes', 'photo_path', 'is_active', 'created_at', 'updated_at'])
            ->map(fn (Location $l) => [
                'id' => $l->id,
                'name' => $l->name,
                'slug' => $l->slug,
                'description' => $l->description,
                'address' => $l->address,
                'notes' => $l->notes,
                'is_active' => (bool) $l->is_active,
                'photo_path' => $l->photo_path,
                'photo_url' => $l->photo_url,
                'created_at' => $l->created_at,
                'updated_at' => $l->updated_at,
            ]);

        return Inertia::render('dashboard/locations/LocationsIndex', [
            'locations' => $locations,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('dashboard/locations/CreateLocation');
    }

    public function store(StoreLocationRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $loc = new Location;
        $loc->fill([
            'name' => $data['name'],
            'slug' => $data['slug'] ?? null,
            'description' => $data['description'],
            'address' => $data['address'] ?? null,
            'notes' => $data['notes'] ?? null,
            'photo_path' => $data['photo_path'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);
        $loc->save();

        return redirect()->route('dashboard.locations')->with('success', 'Location created.');
    }

    public function edit(Location $location): Response
    {
        return Inertia::render('dashboard/locations/EditLocation', [
            'location' => $location->only(['id', 'name', 'slug', 'description', 'address', 'notes', 'photo_path', 'is_active']) + [
                'photo_url' => $location->photo_url,
            ],
        ]);
    }

    public function update(UpdateLocationRequest $request, Location $location): RedirectResponse
    {
        $data = $request->validated();
        $location->update([
            'name' => $data['name'],
            'slug' => $data['slug'] ?? null,
            'description' => $data['description'],
            'address' => $data['address'] ?? null,
            'notes' => $data['notes'] ?? null,
            'photo_path' => $data['photo_path'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? $location->is_active),
        ]);

        return redirect()->route('dashboard.locations')->with('success', 'Location updated.');
    }

    public function destroy(Request $request, Location $location): RedirectResponse
    {
        // Deletion policy:
        // - Block if location has ANY future timeslots
        // - Allow if only past timeslots (hard delete) with a warning
        $now = now();

        $hasFuture = Timeslot::query()
            ->where('location_id', $location->id)
            ->where('start_at', '>', $now)
            ->exists();

        if ($hasFuture) {
            return redirect()->back()->with('error', 'This location has upcoming timeslots and cannot be deleted.');
        }

        // OK to delete. Wrap in transaction to be safe.
        DB::transaction(function () use ($location) {
            $location->delete();
        });

        return redirect()->route('dashboard.locations')->with('success', 'Location deleted.');
    }

    // Lightweight typeahead search (name only)
    public function search(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $limit = (int) $request->query('limit', 8);
        $limit = max(1, min($limit, 25));

        $query = Location::query()->active();
        if ($q !== '') {
            $query->where('name', 'like', "%{$q}%");
        }

        $locations = $query->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'photo_path'])
            ->map(fn (Location $l) => [
                'id' => $l->id,
                'name' => $l->name,
                'photo_url' => $l->photo_url,
            ]);

        return response()->json($locations->values());
    }
}
