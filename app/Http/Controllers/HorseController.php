<?php

namespace App\Http\Controllers;

use App\Models\Horse;
use App\Http\Requests\StoreHorseRequest;
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
}
