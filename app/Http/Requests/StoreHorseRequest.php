<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreHorseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'breed' => ['nullable', 'string', 'max:255'],
            'is_bookable' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
            // Media manager inputs (either upload or existing library path)
            'photo' => ['nullable', 'mimetypes:image/png,image/jpeg,image/webp', 'max:5120'],
            'photo_path' => ['nullable', 'string', 'max:1024'],
        ];
    }
}
