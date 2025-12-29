<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTimeslotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'is_group' => ['nullable', 'boolean'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'service_name' => ['nullable', 'string', 'max:255'],
            'trainer_ids' => ['nullable', 'array'],
            'trainer_ids.*' => ['integer', 'exists:trainers,id'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'horse_ids' => ['nullable', 'array'],
            'horse_ids.*' => ['integer', 'exists:horses,id'],
        ];
    }
}
