<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SiteSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PaymentSettingsController extends Controller
{
    private const DEFAULTS = [
        'method' => 'bank_transfer',
        'wompi' => ['public_key' => '', 'private_key' => '', 'events_key' => ''],
        'bank_transfer' => [
            'bank_name' => '', 'account_number' => '', 'account_holder' => '',
            'instructions' => 'Toma captura del comprobante de tu pago y envíalo por WhatsApp para apartar tu cita.',
            'account_image_path' => null,
        ],
    ];

    public function __construct(private SiteSettingsService $settings)
    {
    }

    public function edit(): View
    {
        $payment = array_replace_recursive(self::DEFAULTS, $this->settings->get('payment', []));

        return view('admin.payment-settings.edit', [
            'payment' => $payment,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'method' => ['required', Rule::in(['bank_transfer', 'wompi'])],
            'wompi_public_key' => ['nullable', 'string', 'max:255'],
            'wompi_private_key' => ['nullable', 'string', 'max:255'],
            'wompi_events_key' => ['nullable', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:60'],
            'account_holder' => ['nullable', 'string', 'max:255'],
            'instructions' => ['nullable', 'string', 'max:1000'],
            'account_image' => ['nullable', 'image', 'max:2048', 'dimensions:max_width=2000,max_height=2000'],
        ], [
            'account_image.image' => 'Debe ser una imagen (PNG, JPG o similar).',
            'account_image.max' => 'La imagen no debe superar 2 MB.',
            'account_image.dimensions' => 'La imagen no debe superar 2000×2000 píxeles.',
        ]);

        $current = array_replace_recursive(self::DEFAULTS, $this->settings->get('payment', []));

        $accountImagePath = $current['bank_transfer']['account_image_path'];
        if ($request->hasFile('account_image')) {
            if (! empty($accountImagePath)) {
                Storage::disk('public')->delete($accountImagePath);
            }
            $accountImagePath = $request->file('account_image')->store('payments', 'public');
        }

        $this->settings->set('payment', [
            'method' => $data['method'],
            'wompi' => [
                'public_key' => $data['wompi_public_key'] ?? '',
                'private_key' => $data['wompi_private_key'] ?? '',
                'events_key' => $data['wompi_events_key'] ?? '',
            ],
            'bank_transfer' => [
                'bank_name' => $data['bank_name'] ?? '',
                'account_number' => $data['account_number'] ?? '',
                'account_holder' => $data['account_holder'] ?? '',
                'instructions' => $data['instructions'] ?? self::DEFAULTS['bank_transfer']['instructions'],
                'account_image_path' => $accountImagePath,
            ],
        ]);

        return back()->with('status', 'Configuración de pagos actualizada.');
    }
}
