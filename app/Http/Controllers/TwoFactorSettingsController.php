<?php

namespace App\Http\Controllers;

use App\Models\TrustedDevice;
use App\Services\SecurityAvailability;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorSettingsController extends Controller
{
    public function edit(SecurityAvailability $availability): View
    {
        $user = Auth::user();
        $pendingSecret = session('totp_setup_secret');

        return view('profile.two-factor', [
            'user' => $user,
            'channels' => $availability->channels(),
            'devices' => $user->trustedDevices()->latest('last_used_at')->get(),
            'pendingSecret' => $pendingSecret,
            'qrCodeSvg' => $pendingSecret ? $this->renderQrCode($user, $pendingSecret) : null,
        ]);
    }

    private function renderQrCode(\App\Models\User $user, string $secret): string
    {
        $issuer = config('site.security.totp_issuer', config('site.name'));
        $otpauthUri = (new Google2FA())->getQRCodeUrl($issuer, $user->email, $secret);

        $renderer = new ImageRenderer(new RendererStyle(200), new SvgImageBackEnd());

        return (new Writer($renderer))->writeString($otpauthUri);
    }

    /**
     * Genera un secreto TOTP nuevo y lo guarda TEMPORALMENTE en sesión — no se
     * persiste en la cuenta hasta confirmarse con un código válido.
     */
    public function setupTotp(): RedirectResponse
    {
        $secret = (new Google2FA())->generateSecretKey();

        session(['totp_setup_secret' => $secret]);

        return redirect()->route('two-factor.edit');
    }

    public function confirmTotp(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string', 'size:6']]);

        $secret = session('totp_setup_secret');
        if (! $secret) {
            return back()->withErrors(['code' => 'No hay una configuración de autenticador en curso.']);
        }

        if (! (new Google2FA())->verifyKey($secret, $request->string('code'))) {
            return back()->withErrors(['code' => 'Código incorrecto.']);
        }

        $user = Auth::user();
        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
            'two_factor_method' => 'totp',
        ])->save();

        session()->forget('totp_setup_secret');

        return redirect()->route('two-factor.edit')->with('status', 'Autenticador activado.');
    }

    public function updateMethod(Request $request, SecurityAvailability $availability): RedirectResponse
    {
        $request->validate(['method' => ['required', 'in:email,sms,whatsapp']]);

        $method = $request->string('method')->toString();
        $available = $availability->channels();

        if (empty($available[$method])) {
            return back()->withErrors(['method' => 'Ese canal no está disponible todavía.']);
        }

        Auth::user()->update(['two_factor_method' => $method]);

        return back()->with('status', 'Método de acceso actualizado.');
    }

    public function forgetDevice(TrustedDevice $device): RedirectResponse
    {
        abort_unless($device->user_id === Auth::id(), 403);

        $device->delete();

        return back()->with('status', 'Dispositivo olvidado.');
    }
}
