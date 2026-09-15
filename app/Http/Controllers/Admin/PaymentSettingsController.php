<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SiteSettingsService;
use App\Services\WompiPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PaymentSettingsController extends Controller
{
    private const DEFAULTS = [
        'method' => 'bank_transfer',
        'wompi' => ['mode' => 'sandbox', 'app_id' => '', 'api_secret' => ''],
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
            'wompi_mode' => ['nullable', Rule::in(['sandbox', 'production'])],
            'wompi_app_id' => ['nullable', 'string', 'max:255'],
            'wompi_api_secret' => ['nullable', 'string', 'max:255'],
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
                'mode' => $data['wompi_mode'] ?? self::DEFAULTS['wompi']['mode'],
                'app_id' => $data['wompi_app_id'] ?? '',
                'api_secret' => $data['wompi_api_secret'] ?? '',
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

    /**
     * Solo intenta autenticarse contra Wompi con las credenciales ya
     * guardadas, sin crear ningún enlace de pago — feedback inmediato de si
     * el App ID/API Secret son correctos antes de que una paciente real
     * intente pagar.
     */
    public function testWompi(WompiPaymentService $wompi): RedirectResponse
    {
        try {
            $wompi->testConnection();

            return back()->with('wompi_test_result', [
                'ok' => true,
                'message' => 'Conexión exitosa: Wompi aceptó las credenciales.',
            ]);
        } catch (\Throwable $e) {
            return back()->with('wompi_test_result', [
                'ok' => false,
                'message' => 'No se pudo conectar con Wompi: '.$e->getMessage(),
            ]);
        }
    }
}
