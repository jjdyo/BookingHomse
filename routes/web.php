<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\TimeslotController;
use App\Http\Controllers\HorseController;
use App\Http\Controllers\TrainerController;
use App\Http\Controllers\BookingController;

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

    // Bookings — edit/update (URL retained under bookings for per-booking editing)
    Route::get('/dashboard/bookings/{booking}/edit', [BookingController::class, 'edit'])
        ->name('dashboard.bookings.edit');
    Route::put('/bookings/{booking}', [BookingController::class, 'update'])
        ->name('bookings.update');

    Route::get('/timeslots/create', [TimeslotController::class, 'create'])->name('timeslots.create');
    Route::post('/timeslots', [TimeslotController::class, 'store'])->name('timeslots.store');
    // Timeslots edit/update (admin)
    Route::get('/dashboard/timeslots/{timeslot}/edit', [TimeslotController::class, 'edit'])->name('dashboard.timeslots.edit');
    Route::put('/timeslots/{timeslot}', [TimeslotController::class, 'update'])->name('timeslots.update');

    // Booking placeholder — requires auth; unauthenticated users will be redirected to login and then back here
    Route::get('/book/timeslot/{timeslot}', [TimeslotController::class, 'bookPlaceholder'])->name('book.timeslot');
});

require __DIR__.'/settings.php';
