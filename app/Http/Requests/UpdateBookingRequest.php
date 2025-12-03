<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'timeslot_id' => ['required', 'integer', 'exists:timeslots,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'horse_id' => ['nullable', 'integer', 'exists:horses,id'],
            'status' => ['required', 'in:pending,confirmed,cancelled,completed,no_show'],
            'payment_status' => ['required', 'in:unpaid,paid,refunded'],
            'cancel_reason' => ['nullable', 'string'],
        ];
    }
}
