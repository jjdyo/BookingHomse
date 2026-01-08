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
            // Media manager inputs (either upload or existing library path)
            'photo' => ['nullable', 'mimetypes:image/png,image/jpeg,image/webp', 'max:5120'],
            'photo_path' => ['nullable', 'string', 'max:1024'],
            'cooldown_duration' => ['nullable', 'integer', 'min:1'],
            'cooldown_unit' => ['nullable', 'string', 'in:minutes,hours,days'],
        ];
    }

    protected function passedValidation(): void
    {
        if ($this->cooldown_unit === 'minutes') {
            $this->merge(['cooldown_duration' => min($this->cooldown_duration, 59)]);
        } elseif ($this->cooldown_unit === 'hours') {
            $this->merge(['cooldown_duration' => min($this->cooldown_duration, 23)]);
        } elseif ($this->cooldown_unit === 'days') {
            $this->merge(['cooldown_duration' => min($this->cooldown_duration, 7)]);
        }
    }
}
