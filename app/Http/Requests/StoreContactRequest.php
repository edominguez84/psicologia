<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'             => ['required', 'string', 'max:120'],
            'email'            => ['required', 'email', 'max:180'],
            'phone'            => ['nullable', 'string', 'max:40'],
            'subject'          => ['nullable', 'string', 'max:160'],
            'message'          => ['required', 'string', 'min:10', 'max:4000'],
            'preferred_contact'=> ['nullable', 'in:whatsapp,email,llamada'],
            'consent'          => ['accepted'],
            // honeypot anti-spam: debe llegar vacío
            'website'          => ['nullable', 'size:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'consent.accepted' => 'Debes aceptar la política de privacidad para continuar.',
            'website.size'     => 'Envío no válido.',
            'message.min'      => 'Cuéntame un poco más para poder ayudarte (mínimo 10 caracteres).',
        ];
    }
}
