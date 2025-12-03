<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHorseRequest extends FormRequest
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
        ];
    }
}
