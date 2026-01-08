<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTimeslotRequest;
use App\Http\Requests\UpdateTimeslotRequest;
use App\Models\SiteConfig;
use App\Models\Timeslot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

        $slots = $query->with([
            'location',
            'trainers' => function ($q) {
                $q->select('trainers.id', 'trainers.name');
            },
            'horses' => function ($q) {
                $q->select('horses.id', 'horses.name');
            },
        ])->orderBy('start_at')->get();

        return $slots->map(function (Timeslot $t) {
            $color = $t->color ?: '#3B82F6';

            return [
                'id' => $t->id,
                'title' => $t->title,
                'start' => $t->start_at->toIso8601String(),
                'end' => $t->end_at->toIso8601String(),
                'backgroundColor' => $color,
                'borderColor' => $color,
                'extendedProps' => [
                    'description' => $t->description,
                    'capacity' => $t->capacity,
                    'price' => $t->price,
                    'service_name' => $t->service_name,
                    // Multi-trainer support
                    'trainer_names' => $t->trainers->pluck('name')->values(),
                    'trainer_label' => $t->trainers->pluck('name')->implode(', '),
                    'horse_names' => $t->horses->pluck('name')->values(),
                    'horse_label' => $t->horses->pluck('name')->implode(', '),
                    'location_name' => optional($t->location)->name,
                    'location_address' => optional($t->location)->address,
                    'color' => $t->color,
                ],
            ];
        });
    }

    public function create(): Response
    {
        $config = SiteConfig::instance();

        return Inertia::render('timeslots/CreateTimeslot', [
            'warnings' => [
                'trainers' => (bool) $config->warn_overbook_trainers,
                'horses' => (bool) $config->warn_overbook_horses,
                'horse_cooldown' => (bool) $config->warn_horse_cooldown,
                'timeslots' => (bool) $config->warn_overbook_timeslots,
            ],
        ]);
    }

    public function store(StoreTimeslotRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $data['capacity'] = $data['capacity'] ?? 1;
        $data['is_group'] = (bool) ($data['is_group'] ?? false);
        $data['price'] = $data['price'] ?? 0;
        $data['created_by'] = Auth::id();

        $horseIds = (array) ($data['horse_ids'] ?? []);
        $trainerIds = (array) ($data['trainer_ids'] ?? []);
        unset($data['horse_ids']);
        unset($data['trainer_ids']);

        $timeslot = null;
        DB::transaction(function () use (&$timeslot, $data, $horseIds, $trainerIds) {
            $timeslot = Timeslot::create($data);
            if (! empty($horseIds)) {
                $timeslot->horses()->sync(array_values(array_unique($horseIds)));
            }
            if (! empty($trainerIds)) {
                $timeslot->trainers()->sync(array_values(array_unique($trainerIds)));
            }
        });

        Log::info('Timeslot created', [
            'id' => optional($timeslot)->id,
            'title' => optional($timeslot)->title,
            'start_at' => optional($timeslot?->start_at)->toIso8601String(),
            'end_at' => optional($timeslot?->end_at)->toIso8601String(),
            'trainer_ids_count' => count($trainerIds),
            'horse_ids_count' => count($horseIds),
        ]);

        return redirect()->route('request-booking')->with('success', 'Timeslot created.');
    }

    public function edit(Timeslot $timeslot): Response
    {
        $config = SiteConfig::instance();

        // Ensure relations are available for horse and trainer details used by the UI
        $timeslot->loadMissing('horses:id,name,breed,photo_path');
        $timeslot->loadMissing(['trainers' => function ($q) {
            // Provide fields for TrainerMultiTypeahead cards
            // Qualify columns to avoid ambiguous names on SQLite
            $q->select('trainers.id', 'trainers.name', 'trainers.title', 'trainers.photo_path');
        }]);
        // Also load location so we can provide the initial display name for the typeahead
        $timeslot->loadMissing(['location:id,name']);

        return Inertia::render('timeslots/EditTimeslot', [
            'timeslot' => [
                'id' => $timeslot->id,
                'title' => $timeslot->title,
                'description' => $timeslot->description,
                'start_at' => $timeslot->start_at?->toIso8601String(),
                'end_at' => $timeslot->end_at?->toIso8601String(),
                'capacity' => $timeslot->capacity,
                'is_group' => (bool) $timeslot->is_group,
                'price' => $timeslot->price,
                'service_name' => $timeslot->service_name,
                'trainer_ids' => $timeslot->trainers->pluck('id')->values(),
                'trainers' => $timeslot->trainers->map(fn ($tr) => [
                    'id' => $tr->id,
                    'name' => $tr->name,
                    'title' => $tr->title,
                    'photo_url' => $tr->photo_url ?? null,
                ])->values(),
                'location_id' => $timeslot->location_id,
                'location_name' => optional($timeslot->location)->name,
                'horse_ids' => $timeslot->horses->pluck('id')->values(),
                'horses' => $timeslot->horses->map(function ($h) {
                    return [
                        'id' => $h->id,
                        'name' => $h->name,
                        'breed' => $h->breed,
                        'photo_url' => $h->photo_url,
                    ];
                })->values(),
            ],
            'warnings' => [
                'trainers' => (bool) $config->warn_overbook_trainers,
                'horses' => (bool) $config->warn_overbook_horses,
                'horse_cooldown' => (bool) $config->warn_horse_cooldown,
                'timeslots' => (bool) $config->warn_overbook_timeslots,
            ],
        ]);
    }

    public function update(UpdateTimeslotRequest $request, Timeslot $timeslot): RedirectResponse
    {
        $data = $request->validated();

        $data['capacity'] = $data['capacity'] ?? 1;
        $data['is_group'] = (bool) ($data['is_group'] ?? false);
        $data['price'] = $data['price'] ?? 0;

        $horseIds = (array) ($data['horse_ids'] ?? []);
        $trainerIds = (array) ($data['trainer_ids'] ?? []);
        unset($data['horse_ids']);
        unset($data['trainer_ids']);

        DB::transaction(function () use ($timeslot, $data, $horseIds, $trainerIds) {
            $timeslot->update($data);
            $timeslot->horses()->sync(array_values(array_unique($horseIds)));
            $timeslot->trainers()->sync(array_values(array_unique($trainerIds)));
        });

        return redirect()->route('dashboard.timeslots')->with('success', 'Timeslot updated.');
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

    /**
     * Pre-submit conflicts check for timeslot creation.
     * Accepts: title, start_at, end_at, optional trainer_ids[], optional horse_ids[]
     * Returns: { conflicts: { timeslots: [...], trainers: [...], horses: [...] } }
     */
    public function checkConflicts(Request $request)
    {
        $request->validate([
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'trainer_ids' => ['nullable', 'array'],
            'trainer_ids.*' => ['integer', 'exists:trainers,id'],
            'horse_ids' => ['nullable', 'array'],
            'horse_ids.*' => ['integer', 'exists:horses,id'],
            // Optional: when editing an existing timeslot, exclude it from overlap set
            'exclude_id' => ['nullable', 'integer'],
        ]);

        $start = Carbon::parse($request->input('start_at'));
        $end = Carbon::parse($request->input('end_at'));
        $trainerIds = (array) $request->input('trainer_ids', []);
        $horseIds = (array) $request->input('horse_ids', []);
        $excludeId = $request->input('exclude_id');

        Log::info('Timeslot conflicts check: request', [
            'start_at' => $start?->toIso8601String(),
            'end_at' => $end?->toIso8601String(),
            'trainer_ids_count' => count($trainerIds),
            'horse_ids_count' => count($horseIds),
            'exclude_id' => $excludeId,
        ]);

        // Overlapping timeslots
        $overlappingQuery = Timeslot::query()
            ->where('start_at', '<', $end)
            ->where('end_at', '>', $start)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->orderBy('start_at')
            ->with(['trainers' => function ($q) {
                $q->select('trainers.id', 'trainers.name');
            }]);

        $overlapping = $overlappingQuery->get();

        $timeslotConflicts = $overlapping->map(fn (Timeslot $t) => [
            'id' => $t->id,
            'title' => $t->title,
            'start_at' => $t->start_at->toIso8601String(),
            'end_at' => $t->end_at->toIso8601String(),
            'trainer_names' => $t->trainers->pluck('name')->values(),
            'service_name' => $t->service_name,
        ]);

        $trainerConflicts = collect();
        if (! empty($trainerIds)) {
            $trainerOverlaps = Timeslot::query()
                ->where('start_at', '<', $end)
                ->where('end_at', '>', $start)
                ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
                ->whereHas('trainers', function ($q) use ($trainerIds) {
                    $q->whereIn('trainers.id', $trainerIds);
                })
                ->with(['trainers' => function ($q) use ($trainerIds) {
                    $q->whereIn('trainers.id', $trainerIds)->select('trainers.id', 'trainers.name');
                }])
                ->get();

            $trainerConflicts = $trainerOverlaps->map(function (Timeslot $t) {
                return [
                    'id' => $t->id,
                    'title' => $t->title,
                    'start_at' => $t->start_at->toIso8601String(),
                    'end_at' => $t->end_at->toIso8601String(),
                    'service_name' => $t->service_name,
                    'trainers' => $t->trainers->map(fn ($tr) => ['id' => $tr->id, 'name' => $tr->name])->values(),
                ];
            });
        }

        $horseConflicts = collect();
        $cooldownConflicts = collect();
        if (! empty($horseIds)) {
            // Standard overlap check
            $horseOverlaps = Timeslot::query()
                ->where('start_at', '<', $end)
                ->where('end_at', '>', $start)
                ->whereHas('horses', function ($q) use ($horseIds) {
                    $q->whereIn('horses.id', $horseIds);
                })
                ->with(['horses' => function ($q) use ($horseIds) {
                    $q->whereIn('horses.id', $horseIds)->select('horses.id', 'horses.name');
                }])
                ->get();

            $horseConflicts = $horseOverlaps->map(function (Timeslot $t) {
                return [
                    'id' => $t->id,
                    'title' => $t->title,
                    'start_at' => $t->start_at->toIso8601String(),
                    'end_at' => $t->end_at->toIso8601String(),
                    'service_name' => $t->service_name,
                    'horses' => $t->horses->map(fn ($h) => ['id' => $h->id, 'name' => $h->name])->values(),
                ];
            });

            // Cooldown check
            $horsesWithCooldown = \App\Models\Horse::whereIn('id', $horseIds)
                ->whereNotNull('cooldown_duration')
                ->whereNotNull('cooldown_unit')
                ->get(['id', 'name', 'cooldown_duration', 'cooldown_unit']);

            foreach ($horsesWithCooldown as $horse) {
                $duration = $horse->cooldown_duration;
                $unit = $horse->cooldown_unit;
                $cooldownMinutes = match ($unit) {
                    'minutes' => $duration,
                    'hours' => $duration * 60,
                    'days' => $duration * 24 * 60,
                    default => 0,
                };

                if ($cooldownMinutes <= 0) {
                    continue;
                }

                // A cooldown violation exists if there's a timeslot E such that:
                // T overlaps with [E.start_at, E.end_at + cooldown]
                // OR
                // E overlaps with [T.start_at, T.end_at + cooldown]
                // (This simplifies to: T.start < E.end + C AND E.start < T.end + C)

                $violatingTimeslots = Timeslot::query()
                    ->whereHas('horses', fn ($q) => $q->where('horses.id', $horse->id))
                    ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
                    // Standard overlap is already handled by $horseConflicts,
                    // so we only care about "near-misses" within the cooldown period.
                    // However, for simplicity, we can just find all that violate the formula.
                    ->get()
                    ->filter(function (Timeslot $t) use ($start, $end, $cooldownMinutes) {
                        $eStart = $t->start_at;
                        $eEnd = $t->end_at;

                        // Check T.start < E.end + C AND E.start < T.end + C
                        $conflict = $start->lt($eEnd->copy()->addMinutes($cooldownMinutes)) &&
                                   $eStart->lt($end->copy()->addMinutes($cooldownMinutes));

                        // Only report if it's NOT a standard overlap (to avoid duplicate warnings)
                        $standardOverlap = $start->lt($eEnd) && $eStart->lt($end);

                        return $conflict && ! $standardOverlap;
                    });

                foreach ($violatingTimeslots as $t) {
                    $cooldownConflicts->push([
                        'id' => $t->id,
                        'title' => $t->title,
                        'start_at' => $t->start_at->toIso8601String(),
                        'end_at' => $t->end_at->toIso8601String(),
                        'service_name' => $t->service_name,
                        'horse' => ['id' => $horse->id, 'name' => $horse->name],
                        'cooldown_text' => "{$duration} {$unit}",
                    ]);
                }
            }
        }

        $payload = [
            'conflicts' => [
                'timeslots' => $timeslotConflicts->values(),
                'trainers' => $trainerConflicts->values(),
                'horses' => $horseConflicts->values(),
                'cooldowns' => $cooldownConflicts->values(),
            ],
        ];

        Log::info('Timeslot conflicts check: result', [
            'timeslots_count' => count($payload['conflicts']['timeslots']),
            'trainers_count' => count($payload['conflicts']['trainers']),
            'horses_count' => count($payload['conflicts']['horses']),
            'example_timeslot_ids' => collect($payload['conflicts']['timeslots'])->take(3)->pluck('id'),
            'example_trainer_ids' => collect($payload['conflicts']['trainers'])->take(3)->pluck('id'),
            'example_horse_conflict_ts_ids' => collect($payload['conflicts']['horses'])->take(3)->pluck('id'),
            'window' => [
                'start' => $start?->toIso8601String(),
                'end' => $end?->toIso8601String(),
            ],
        ]);

        return response()->json($payload);
    }

    public function sidebar()
    {
        $config = SiteConfig::instance();

        if (! $config->show_event_feed) {
            return response()->json([
                'now_happening' => [],
                'upcoming' => [],
            ]);
        }

        $now = Carbon::now();
        $lookahead = $now->copy()->addDays($config->event_feed_lookahead_days);

        $nowHappening = Timeslot::query()
            ->where('start_at', '<=', $now)
            ->where('end_at', '>=', $now)
            ->orderBy('start_at')
            ->get(['id', 'title', 'description', 'start_at', 'end_at']);

        $upcoming = Timeslot::query()
            ->where('start_at', '>', $now)
            ->where('start_at', '<=', $lookahead)
            ->orderBy('start_at')
            ->get(['id', 'title', 'description', 'start_at', 'end_at']);

        $transform = function (Timeslot $t) {
            return [
                'id' => $t->id,
                'title' => $t->title,
                'description' => $t->description ? (mb_strlen($t->description) > 100 ? mb_substr($t->description, 0, 97).'...' : $t->description) : null,
                'start_at' => $t->start_at->toIso8601String(),
                'end_at' => $t->end_at->toIso8601String(),
                'url' => route('book.timeslot', $t->id),
            ];
        };

        return response()->json([
            'now_happening' => $nowHappening->map($transform),
            'upcoming' => $upcoming->map($transform),
        ]);
    }
}
