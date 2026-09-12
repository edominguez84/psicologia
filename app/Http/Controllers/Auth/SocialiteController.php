<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\OauthConnection;
use App\Models\User;
use App\Services\SecurityAvailability;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\Response;

class SocialiteController extends Controller
{
    public function redirect(string $provider, SecurityAvailability $availability): RedirectResponse|Response
    {
        if (empty($availability->oauth()[$provider])) {
            abort(404);
        }

        return Socialite::driver($provider)->redirect();
    }

    /**
     * Login social solo para cuentas YA existentes (mismo criterio que el
     * resto del sitio: sin autorregistro). Se busca primero por la conexión
     * ya enlazada; si es la primera vez, por email; si no hay ninguna cuenta
     * con ese correo, no se crea una nueva — se pide contactar al admin.
     *
     * Un login social exitoso omite el reto 2FA propio de la app: el
     * proveedor (Google/Facebook/Microsoft) ya constituye una autenticación
     * fuerte por sí mismo, y encadenar otro código sería fricción redundante
     * — es el comportamiento estándar de "iniciar sesión con Google" en la
     * mayoría de sitios.
     */
    public function callback(string $provider, SecurityAvailability $availability): RedirectResponse
    {
        if (empty($availability->oauth()[$provider])) {
            abort(404);
        }

        $socialUser = Socialite::driver($provider)->user();

        $connection = OauthConnection::where('provider', $provider)
            ->where('provider_user_id', $socialUser->getId())
            ->first();

        $user = $connection?->user;

        if (! $user) {
            $user = User::where('email', $socialUser->getEmail())->first();

            if ($user) {
                OauthConnection::create([
                    'user_id' => $user->id,
                    'provider' => $provider,
                    'provider_user_id' => $socialUser->getId(),
                ]);
            }
        }

        if (! $user) {
            // No es una fuga de información explotable: quien llega aquí ya
            // demostró ser dueño de ese email ante el proveedor OAuth.
            return redirect()->route('login')
                ->with('status', 'No existe una cuenta con ese email. Contacta con la administradora.');
        }

        if ($user->isBanned()) {
            return redirect()->route('login')->with('status', 'Tu cuenta ha sido suspendida.');
        }

        Auth::login($user);
        request()->session()->regenerate();

        return redirect()->intended(route('admin.dashboard', absolute: false));
    }
}
