<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\TimeslotController;
use App\Http\Controllers\HorseController;
use App\Http\Controllers\TrainerController;

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
    Route::get('/dashboard/bookings', fn () => Inertia::render('dashboard/Bookings'))
        ->name('dashboard.bookings');

    Route::get('/dashboard/horses', [HorseController::class, 'index'])
        ->name('dashboard.horses');
    Route::get('/dashboard/horses/create', [HorseController::class, 'create'])
        ->name('dashboard.horses.create');
    Route::post('/horses', [HorseController::class, 'store'])
        ->name('horses.store');

    // Trainers
    Route::get('/dashboard/trainers', [TrainerController::class, 'index'])
        ->name('dashboard.trainers');
    Route::get('/dashboard/trainers/create', [TrainerController::class, 'create'])
        ->name('dashboard.trainers.create');
    Route::post('/trainers', [TrainerController::class, 'store'])
        ->name('trainers.store');

    Route::get('/timeslots/create', [TimeslotController::class, 'create'])->name('timeslots.create');
    Route::post('/timeslots', [TimeslotController::class, 'store'])->name('timeslots.store');

    // Booking placeholder — requires auth; unauthenticated users will be redirected to login and then back here
    Route::get('/book/timeslot/{timeslot}', [TimeslotController::class, 'bookPlaceholder'])->name('book.timeslot');
});

require __DIR__.'/settings.php';
