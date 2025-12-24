<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:locations,name'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('locations', 'slug')],
            'description' => ['required', 'string'],
            'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'photo_path' => ['nullable', 'string', 'max:1024'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
