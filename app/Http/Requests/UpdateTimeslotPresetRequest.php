<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTimeslotPresetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'preset_title' => ['required', 'string', 'max:255'],
            'preset_description' => ['nullable', 'string'],

            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'is_group' => ['nullable', 'boolean'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'service_name' => ['nullable', 'string', 'max:255'],
            'trainer_id' => ['nullable', 'integer', 'exists:users,id'],
            'trainer_name' => ['nullable', 'string', 'max:255'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'color' => ['nullable', 'string', 'max:9'],

            'horse_ids' => ['nullable', 'array'],
            'horse_ids.*' => ['integer', 'exists:horses,id'],
        ];
    }
}
