<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateBookingRequest;
use App\Models\Booking;
use App\Models\Horse;
use App\Models\Timeslot;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    public function edit(Booking $booking): Response
    {
        $booking->load(['timeslot', 'user', 'horse']);

        $users = User::query()->orderBy('name')->get(['id', 'name']);
        $timeslots = Timeslot::query()->orderBy('start_at')->get(['id', 'title', 'start_at']);
        $horses = Horse::query()->ordered()->get(['id', 'name']);

        return Inertia::render('dashboard/bookings/EditBooking', [
            'booking' => $booking->only([
                'id', 'timeslot_id', 'user_id', 'horse_id', 'status', 'payment_status', 'paid_at', 'cancelled_at', 'cancelled_by', 'cancel_reason',
            ]),
            'users' => $users,
            'timeslots' => $timeslots,
            'horses' => $horses,
        ]);
    }

    public function update(UpdateBookingRequest $request, Booking $booking): RedirectResponse
    {
        $data = $request->validated();

        $booking->timeslot_id = $data['timeslot_id'];
        $booking->user_id = $data['user_id'] ?? null;
        $booking->horse_id = $data['horse_id'] ?? null;
        $booking->status = $data['status'];
        $booking->payment_status = $data['payment_status'];

        // Handle cancellation metadata
        if ($booking->status === 'cancelled') {
            if (! $booking->cancelled_at) {
                $booking->cancelled_at = now();
                $booking->cancelled_by = auth()->id();
            }
            $booking->cancel_reason = $data['cancel_reason'] ?? $booking->cancel_reason;
        } else {
            // When not cancelled, clear cancellation metadata
            $booking->cancel_reason = null;
            $booking->cancelled_at = null;
            $booking->cancelled_by = null;
        }

        // Auto-set paid_at when payment_status becomes paid (and not set yet)
        if ($booking->payment_status === 'paid' && ! $booking->paid_at) {
            $booking->paid_at = now();
        }

        $booking->save();

        return redirect()->route('dashboard.timeslots')->with('success', 'Booking updated.');
    }
}
