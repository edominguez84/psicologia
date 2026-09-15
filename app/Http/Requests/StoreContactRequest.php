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
            // Solo lo manda el modal de "llamada gratis" cuando la persona
            // elige un horario del catálogo — el formulario de contacto
            // general nunca lo incluye. Se valida que siga libre en
            // withValidator() para evitar una condición de carrera entre dos
            // personas eligiendo el mismo horario casi al mismo tiempo.
            'call_slot_id'     => ['nullable', 'integer', 'exists:call_slots,id'],
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

            $callSlotId = $this->input('call_slot_id');
            if ($callSlotId && ! \App\Models\CallSlot::available()->whereKey($callSlotId)->exists()) {
                $validator->errors()->add('call_slot_id', 'Ese horario ya no está disponible, elige otro.');
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
