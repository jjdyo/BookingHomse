<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTrainerRequest;
use App\Http\Requests\UpdateTrainerRequest;
use App\Models\Trainer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class TrainerController extends Controller
{
    public function index(): Response
    {
        $trainers = Trainer::query()
            ->ordered()
            ->get(['id', 'name', 'title', 'bio', 'photo_path', 'is_bookable', 'created_at', 'updated_at']);

        return Inertia::render('dashboard/trainers/TrainersIndex', [
            'trainers' => $trainers,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('dashboard/trainers/CreateTrainer');
    }

    public function store(StoreTrainerRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Prefer selected library path when provided; otherwise process uploaded file
        $photoPath = $data['photo_path'] ?? null;
        if (! $photoPath && $request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('trainers', 'public');
        }

        Trainer::create([
            'name' => $data['name'],
            'title' => $data['title'] ?? null,
            'bio' => $data['bio'] ?? null,
            'photo_path' => $photoPath,
            'is_bookable' => array_key_exists('is_bookable', $data) ? (bool) $data['is_bookable'] : true,
        ]);

        return redirect()->route('dashboard.trainers')->with('success', 'Trainer created.');
    }

    public function edit(Trainer $trainer): Response
    {
        return Inertia::render('dashboard/trainers/EditTrainer', [
            'trainer' => $trainer->only(['id', 'name', 'title', 'bio', 'photo_path', 'is_bookable']),
        ]);
    }

    public function update(UpdateTrainerRequest $request, Trainer $trainer): RedirectResponse
    {
        $data = $request->validated();

        $update = [
            'name' => $data['name'],
            'title' => $data['title'] ?? null,
            'bio' => $data['bio'] ?? null,
        ];

        if (array_key_exists('is_bookable', $data)) {
            $update['is_bookable'] = (bool) $data['is_bookable'];
        }

        if ($request->hasFile('photo')) {
            // Delete old photo if present
            if ($trainer->photo_path) {
                Storage::disk('public')->delete($trainer->photo_path);
            }
            $update['photo_path'] = $request->file('photo')->store('trainers', 'public');
        } elseif (! empty($data['photo_path'])) {
            // Switch to an existing library image without deleting the old file (it may be referenced elsewhere)
            $update['photo_path'] = $data['photo_path'];
        }

        $trainer->update($update);

        return redirect()->route('dashboard.trainers')->with('success', 'Trainer updated.');
    }

    /**
     * Lightweight search endpoint for typeahead inputs.
     * GET /trainers/search?q=jam
     */
    public function search(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:25'],
        ]);

        $q = trim((string) ($validated['q'] ?? ''));
        $limit = (int) ($validated['limit'] ?? 8);

        $query = Trainer::query()->where('is_bookable', true);
        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%$q%")
                    ->orWhere('title', 'like', "%$q%");
            });
        }

        $results = $query->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'title', 'photo_path']);

        return $results->map(function (Trainer $t) {
            return [
                'id' => $t->id,
                'name' => $t->name,
                'title' => $t->title,
                'photo_url' => $t->photo_path ? Storage::disk('public')->url($t->photo_path) : null,
            ];
        });
    }
}
