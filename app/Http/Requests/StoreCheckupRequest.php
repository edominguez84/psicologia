<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCheckupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'answers'   => ['required', 'array', 'size:5'],
            'answers.*' => ['required', 'integer', 'between:0,3'],
            'email'     => ['nullable', 'email', 'max:180'],
            'website'   => ['nullable', 'size:0'], // honeypot
        ];
    }
}
