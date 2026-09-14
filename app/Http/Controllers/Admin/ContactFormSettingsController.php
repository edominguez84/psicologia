<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SiteSettingsService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ContactFormSettingsController extends Controller
{
    /**
     * Campos fijos del formulario que se pueden ocultar. 'name' y 'message'
     * quedan siempre obligatorios (sin ellos no hay mensaje que enviar), así
     * que no se listan aquí como desactivables.
     */
    public const OPTIONAL_FIXED_FIELDS = [
        'phone' => 'Teléfono / WhatsApp',
        'subject' => 'Asunto',
        'preferred_contact' => '¿Cómo prefieres que te responda?',
    ];

    public function __construct(private SiteSettingsService $settings)
    {
    }

    public function edit(): View
    {
        $saved = $this->settings->get('contact_form', []);

        $visibleFields = [];
        foreach (self::OPTIONAL_FIXED_FIELDS as $key => $label) {
            $visibleFields[$key] = $saved['fields'][$key] ?? true;
        }

        return view('admin.contact-form.edit', [
            'fixedFields' => self::OPTIONAL_FIXED_FIELDS,
            'visibleFields' => $visibleFields,
            'customFields' => $saved['custom_fields'] ?? [],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $fieldKeys = array_keys(self::OPTIONAL_FIXED_FIELDS);

        $data = $request->validate([
            'fields' => ['nullable', 'array'],
            'fields.*' => ['string', 'in:'.implode(',', $fieldKeys)],
        ]);

        $checked = $data['fields'] ?? [];
        $fields = [];
        foreach ($fieldKeys as $key) {
            $fields[$key] = in_array($key, $checked, true);
        }

        $saved = $this->settings->get('contact_form', []);
        $this->settings->set('contact_form', [
            'fields' => $fields,
            'custom_fields' => $saved['custom_fields'] ?? [],
        ]);

        return back()->with('status', 'Campos del formulario actualizados.');
    }

    public function storeCustomField(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:80'],
            'required' => ['nullable', 'boolean'],
        ]);

        $saved = $this->settings->get('contact_form', []);
        $customFields = $saved['custom_fields'] ?? [];
        $customFields[] = [
            'key' => (string) \Illuminate\Support\Str::uuid(),
            'label' => $data['label'],
            'required' => $request->boolean('required'),
        ];

        $this->settings->set('contact_form', [
            'fields' => $saved['fields'] ?? [],
            'custom_fields' => $customFields,
        ]);

        return back()->with('status', 'Campo añadido al formulario.');
    }

    public function destroyCustomField(string $key): RedirectResponse
    {
        $saved = $this->settings->get('contact_form', []);
        $customFields = collect($saved['custom_fields'] ?? [])
            ->reject(fn ($field) => $field['key'] === $key)
            ->values()
            ->all();

        $this->settings->set('contact_form', [
            'fields' => $saved['fields'] ?? [],
            'custom_fields' => $customFields,
        ]);

        return back()->with('status', 'Campo eliminado del formulario.');
    }
}
