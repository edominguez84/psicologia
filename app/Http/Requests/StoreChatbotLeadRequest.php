<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreChatbotLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:120'],
            'email'      => ['required', 'email', 'max:180'],
            'phone'      => ['nullable', 'string', 'max:40'],
            'transcript' => ['nullable', 'array', 'max:40'],
            'transcript.*.from' => ['required_with:transcript', 'in:bot,user'],
            'transcript.*.text' => ['required_with:transcript', 'string', 'max:600'],
            // honeypot anti-spam: debe llegar vacío
            'website' => ['nullable', 'size:0'],
        ];
    }
}
