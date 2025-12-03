<?php

namespace App\Http\Controllers;

use App\Models\Horse;
use App\Http\Requests\StoreHorseRequest;
use App\Http\Requests\UpdateHorseRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HorseController extends Controller
{
    public function index(): Response
    {
        $horses = Horse::query()
            ->ordered()
            ->get(['id', 'name', 'description', 'breed', 'is_bookable', 'notes', 'created_at', 'updated_at']);

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

        $data['is_bookable'] = (bool)($data['is_bookable'] ?? true);

        Horse::create($data);

        return redirect()->route('dashboard.horses')->with('success', 'Horse created.');
    }

    public function edit(Horse $horse): Response
    {
        return Inertia::render('dashboard/horses/EditHorse', [
            'horse' => $horse->only(['id', 'name', 'description', 'breed', 'is_bookable', 'notes']),
        ]);
    }

    public function update(UpdateHorseRequest $request, Horse $horse): RedirectResponse
    {
        $data = $request->validated();

        $data['is_bookable'] = (bool)($data['is_bookable'] ?? false);

        $horse->update($data);

        return redirect()->route('dashboard.horses')->with('success', 'Horse updated.');
    }
}
