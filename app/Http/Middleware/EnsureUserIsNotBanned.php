<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsNotBanned
{
    /**
     * Si un usuario autenticado es baneado a mitad de sesión, su sesión activa
     * debe morir en la siguiente petición — no basta con bloquear futuros
     * intentos de login (eso ya lo hace LoginRequest::authenticate()).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isBanned()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('status', 'Tu cuenta ha sido suspendida.');
        }

        return $next($request);
    }
}
