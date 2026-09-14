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
            // Campos personalizados definidos desde /admin/contact-form,
            // enviados como [{label, value}] — la obligatoriedad de cada uno
            // se valida por separado en withValidator(), contra la
            // configuración guardada, no aquí (el front no puede garantizar
            // qué campos son obligatorios de verdad).
            'custom_fields'    => ['nullable', 'array'],
            'custom_fields.*.label' => ['required_with:custom_fields', 'string', 'max:80'],
            'custom_fields.*.value' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $configured = app(\App\Services\SiteSettingsService::class)
                ->get('contact_form', [])['custom_fields'] ?? [];
            $requiredLabels = collect($configured)->where('required', true)->pluck('label');

            $submitted = collect($this->input('custom_fields', []))
                ->keyBy('label')
                ->map(fn ($f) => trim((string) ($f['value'] ?? '')));

            foreach ($requiredLabels as $label) {
                if (empty($submitted[$label] ?? null)) {
                    $validator->errors()->add('custom_fields', "El campo \"{$label}\" es obligatorio.");
                }
            }
        });
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
