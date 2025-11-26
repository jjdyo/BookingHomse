<?php

namespace App\Http\Controllers;

use App\Models\Timeslot;
use App\Http\Requests\StoreTimeslotRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class TimeslotController extends Controller
{
    public function feed(Request $request)
    {
        // Optional range filtering for FullCalendar
        $startParam = $request->query('start');
        $endParam = $request->query('end');

        $query = Timeslot::query();
        if ($startParam && $endParam) {
            // Normalize incoming ISO8601 strings to Carbon instances to ensure DB-compatible comparisons
            try {
                $rangeStart = Carbon::parse($startParam);
                $rangeEnd = Carbon::parse($endParam);
            } catch (\Throwable $e) {
                $rangeStart = null;
                $rangeEnd = null;
            }

            if ($rangeStart && $rangeEnd) {
                $query->where(function ($q) use ($rangeStart, $rangeEnd) {
                    // Overlaps range: A.start < B.end AND A.end > B.start
                    $q->where('start_at', '<', $rangeEnd)
                      ->where('end_at', '>', $rangeStart);
                });
            }
        }

        $slots = $query->orderBy('start_at')->get();

        return $slots->map(function (Timeslot $t) {
            return [
                'id' => $t->id,
                'title' => $t->title,
                'start' => $t->start_at->toIso8601String(),
                'end' => $t->end_at->toIso8601String(),
                'extendedProps' => [
                    'description' => $t->description,
                    'capacity' => $t->capacity,
                    'price' => $t->price,
                    'service_name' => $t->service_name,
                    'trainer_name' => $t->trainer_name,
                ],
            ];
        });
    }

    public function create(): Response
    {
        return Inertia::render('timeslots/CreateTimeslot');
    }

    public function store(StoreTimeslotRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $data['capacity'] = $data['capacity'] ?? 1;
        $data['is_group'] = (bool)($data['is_group'] ?? false);
        $data['price'] = $data['price'] ?? 0;
        $data['created_by'] = Auth::id();

        $timeslot = Timeslot::create($data);

        return redirect()->route('request-booking')->with('success', 'Timeslot created.');
    }

    public function bookPlaceholder(Timeslot $timeslot): Response
    {
        return Inertia::render('BookTimeslot', [
            'timeslot' => [
                'id' => $timeslot->id,
                'title' => $timeslot->title,
                'description' => $timeslot->description,
                'start_at' => $timeslot->start_at->toIso8601String(),
                'end_at' => $timeslot->end_at->toIso8601String(),
                'price' => $timeslot->price,
            ],
        ]);
    }
}
