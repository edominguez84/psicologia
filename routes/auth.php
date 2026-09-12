<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\TwoFactorSettingsController;
use Illuminate\Support\Facades\Route;

// El registro público está desactivado a propósito: este sitio tiene un único
// rol de administrador, creado con `php artisan make:admin` (ver
// app/Console/Commands/MakeAdminUser.php), no mediante autorregistro. El
// login social (más abajo) tampoco crea cuentas nuevas: solo enlaza un
// proveedor a una cuenta ya existente con el mismo email.
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');

    // Login social (Socialite) — allowlist de proveedores vía whereIn: 404
    // automático si alguien intenta un proveedor no soportado.
    Route::get('auth/{provider}/redirect', [SocialiteController::class, 'redirect'])
        ->whereIn('provider', ['google', 'facebook', 'microsoft'])
        ->name('oauth.redirect');

    Route::get('auth/{provider}/callback', [SocialiteController::class, 'callback'])
        ->whereIn('provider', ['google', 'facebook', 'microsoft'])
        ->name('oauth.callback');
});

// Reto de 2FA: el usuario ya pasó la contraseña pero todavía NO está
// autenticado (Auth::check() es false) — por eso este grupo va fuera de
// 'guest' y de 'auth'. Cada acción valida por su cuenta que exista un
// pending_2fa.user_id en sesión.
Route::middleware('throttle:20,1')->group(function () {
    Route::get('2fa/challenge', [TwoFactorChallengeController::class, 'show'])->name('2fa.challenge');
    Route::post('2fa/verify', [TwoFactorChallengeController::class, 'verify'])->name('2fa.verify');
    Route::post('2fa/resend', [TwoFactorChallengeController::class, 'resend'])->name('2fa.resend');
});

Route::middleware(['auth', 'banned'])->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');

    // Configuración de 2FA por usuario: cualquier persona autenticada
    // administra su propio método (email/sms/whatsapp/totp) y sus
    // dispositivos de confianza — los interruptores de qué canales existen a
    // nivel de sitio siguen siendo exclusivos de /admin/security.
    Route::get('perfil/seguridad', [TwoFactorSettingsController::class, 'edit'])->name('two-factor.edit');
    Route::post('perfil/seguridad/totp/setup', [TwoFactorSettingsController::class, 'setupTotp'])->name('two-factor.totp.setup');
    Route::post('perfil/seguridad/totp/confirm', [TwoFactorSettingsController::class, 'confirmTotp'])->name('two-factor.totp.confirm');
    Route::put('perfil/seguridad/method', [TwoFactorSettingsController::class, 'updateMethod'])->name('two-factor.method.update');
    Route::delete('perfil/seguridad/devices/{device}', [TwoFactorSettingsController::class, 'forgetDevice'])->name('two-factor.devices.forget');
});
