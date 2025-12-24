<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\HorseController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\TimeslotController;
use App\Http\Controllers\TimeslotPresetController;
use App\Http\Controllers\TrainerController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Home', [
        'heroImage' => asset('images/BarnHomse169.png'),
    ]);
})->name('home');

Route::get('/about', function () {
    return Inertia::render('About');
})->name('about');

Route::get('/request-booking', function () {
    return Inertia::render('RequestBooking');
})->name('request-booking');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Timeslots: feed + basic create/store + booking placeholder
Route::get('/timeslots/feed', [TimeslotController::class, 'feed'])->name('timeslots.feed');

Route::middleware(['auth'])->group(function () {
    // Dashboard sub-tabs
    // Timeslots dashboard index (renamed from /dashboard/bookings)
    Route::get('/dashboard/timeslots', fn () => Inertia::render('dashboard/Bookings'))
        ->name('dashboard.timeslots');

    // Timeslot Presets (dashboard)
    Route::get('/dashboard/timeslots/presets', [TimeslotPresetController::class, 'index'])->name('dashboard.timeslots.presets');
    Route::get('/dashboard/timeslots/presets/create', [TimeslotPresetController::class, 'create'])->name('dashboard.timeslots.presets.create');
    Route::post('/dashboard/timeslots/presets', [TimeslotPresetController::class, 'store'])->name('dashboard.timeslots.presets.store');
    Route::get('/dashboard/timeslots/presets/{preset}/edit', [TimeslotPresetController::class, 'edit'])->name('dashboard.timeslots.presets.edit');
    Route::put('/dashboard/timeslots/presets/{preset}', [TimeslotPresetController::class, 'update'])->name('dashboard.timeslots.presets.update');
    Route::delete('/dashboard/timeslots/presets/{preset}', [TimeslotPresetController::class, 'destroy'])->name('dashboard.timeslots.presets.destroy');
    // Deploy preset -> redirect to create with query
    Route::get('/dashboard/timeslots/presets/{preset}/deploy', [TimeslotPresetController::class, 'deploy'])->name('dashboard.timeslots.presets.deploy');
    // JSON show for prefill (expects Accept: application/json)
    Route::get('/dashboard/timeslots/presets/{preset}', [TimeslotPresetController::class, 'show'])->name('dashboard.timeslots.presets.show');

    Route::get('/dashboard/horses', [HorseController::class, 'index'])
        ->name('dashboard.horses');
    Route::get('/dashboard/horses/create', [HorseController::class, 'create'])
        ->name('dashboard.horses.create');
    Route::get('/dashboard/horses/{horse}/edit', [HorseController::class, 'edit'])
        ->name('dashboard.horses.edit');
    Route::post('/horses', [HorseController::class, 'store'])
        ->name('horses.store');
    Route::put('/horses/{horse}', [HorseController::class, 'update'])
        ->name('horses.update');
    // Horses search (typeahead)
    Route::get('/horses/search', [HorseController::class, 'search'])->name('horses.search');

    // Trainers
    Route::get('/dashboard/trainers', [TrainerController::class, 'index'])
        ->name('dashboard.trainers');
    Route::get('/dashboard/trainers/create', [TrainerController::class, 'create'])
        ->name('dashboard.trainers.create');
    Route::get('/dashboard/trainers/{trainer}/edit', [TrainerController::class, 'edit'])
        ->name('dashboard.trainers.edit');
    Route::post('/trainers', [TrainerController::class, 'store'])
        ->name('trainers.store');
    Route::put('/trainers/{trainer}', [TrainerController::class, 'update'])
        ->name('trainers.update');

    // Trainers search (typeahead)
    Route::get('/trainers/search', [TrainerController::class, 'search'])
        ->name('trainers.search');

    // Locations
    Route::get('/dashboard/locations', [LocationController::class, 'index'])
        ->name('dashboard.locations');
    Route::get('/dashboard/locations/create', [LocationController::class, 'create'])
        ->name('dashboard.locations.create');
    Route::get('/dashboard/locations/{location}/edit', [LocationController::class, 'edit'])
        ->name('dashboard.locations.edit');
    Route::post('/locations', [LocationController::class, 'store'])
        ->name('locations.store');
    Route::put('/locations/{location}', [LocationController::class, 'update'])
        ->name('locations.update');
    Route::delete('/locations/{location}', [LocationController::class, 'destroy'])
        ->name('locations.destroy');
    // Locations search (typeahead)
    Route::get('/locations/search', [LocationController::class, 'search'])->name('locations.search');

    // Bookings — edit/update (URL retained under bookings for per-booking editing)
    Route::get('/dashboard/bookings/{booking}/edit', [BookingController::class, 'edit'])
        ->name('dashboard.bookings.edit');
    Route::put('/bookings/{booking}', [BookingController::class, 'update'])
        ->name('bookings.update');

    Route::get('/timeslots/create', [TimeslotController::class, 'create'])->name('timeslots.create');
    Route::post('/timeslots', [TimeslotController::class, 'store'])->name('timeslots.store');
    Route::post('/timeslots/check-conflicts', [TimeslotController::class, 'checkConflicts'])->name('timeslots.checkConflicts');
    // Timeslots edit/update (admin)
    Route::get('/dashboard/timeslots/{timeslot}/edit', [TimeslotController::class, 'edit'])->name('dashboard.timeslots.edit');
    Route::put('/timeslots/{timeslot}', [TimeslotController::class, 'update'])->name('timeslots.update');

    // Booking placeholder — requires auth; unauthenticated users will be redirected to login and then back here
    Route::get('/book/timeslot/{timeslot}', [TimeslotController::class, 'bookPlaceholder'])->name('book.timeslot');

    // Media manager (API-style JSON endpoints)
    Route::get('/media', [MediaController::class, 'index'])->name('media.index');
    Route::get('/media/directories', [MediaController::class, 'directories'])->name('media.directories');
    Route::post('/media', [MediaController::class, 'store'])->name('media.store');
    Route::post('/media/folders', [MediaController::class, 'createFolder'])->name('media.folders.create');
});

require __DIR__.'/settings.php';
