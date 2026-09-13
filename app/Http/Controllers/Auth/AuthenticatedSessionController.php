<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\TrustedDeviceService;
use App\Services\TwoFactorChallengeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(
        LoginRequest $request,
        TrustedDeviceService $trustedDevices,
        TwoFactorChallengeService $twoFactor,
    ): RedirectResponse {
        $user = $request->authenticate();

        // Regenerar la sesión en cuanto las credenciales son válidas (antes
        // de decidir el siguiente paso) mitiga la fijación de sesión.
        $request->session()->regenerate();

        if ($trustedDevices->isTrusted($user, $request)) {
            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();

            return redirect()->intended(route($user->defaultRedirectRouteName(), absolute: false));
        }

        // Todavía NO se llama a Auth::login(): el usuario pasó la contraseña
        // pero falta el segundo factor. Se guarda el estado "pendiente" en
        // sesión para que el reto 2FA sepa a quién verificar.
        $request->session()->put('pending_2fa', [
            'user_id' => $user->id,
            'remember' => $request->boolean('remember'),
        ]);

        $twoFactor->issueChallenge($user);

        return redirect()->route('2fa.challenge');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
