<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $locationId = $this->route('location')?->id ?? null;

        return [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('locations', 'name')->ignore($locationId),
            ],
            'slug' => [
                'nullable', 'string', 'max:255',
                Rule::unique('locations', 'slug')->ignore($locationId),
            ],
            'description' => ['required', 'string'],
            'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'photo_path' => ['nullable', 'string', 'max:1024'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
