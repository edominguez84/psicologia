<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\AppointmentBookingController;
use App\Http\Controllers\PatientProfileController;
use App\Http\Controllers\PatientTestimonialController;
use App\Http\Controllers\TwoFactorSettingsController;
use Illuminate\Support\Facades\Route;

// El autoregistro público SOLO puede crear cuentas de paciente: el rol se
// fuerza en RegisteredUserController y nunca se lee del request. Las cuentas
// de staff (admin/editor/super_admin) se crean desde /admin/staff, exclusivo
// del super_admin (ver Admin\StaffController), o con `php artisan make:admin`
// para el primer arranque. El login social (más abajo) tampoco crea cuentas
// nuevas: solo enlaza un proveedor a una cuenta ya existente con el mismo
// email.
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:6,1');

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

    // Perfil general del paciente: siempre sobre Auth::user(), sin
    // route-model-binding de otro usuario (mismo criterio que arriba).
    Route::get('perfil', [PatientProfileController::class, 'edit'])->name('patient.profile.edit');
    Route::put('perfil', [PatientProfileController::class, 'update'])->name('patient.profile.update');
    Route::post('perfil/foto', [PatientProfileController::class, 'updatePhoto'])->name('patient.profile.photo.update');
    Route::delete('perfil/foto', [PatientProfileController::class, 'destroyPhoto'])->name('patient.profile.photo.destroy');

    // Testimonio propio del paciente: un testimonio activo por cuenta.
    Route::get('perfil/testimonio', [PatientTestimonialController::class, 'edit'])->name('patient.testimonial.edit');
    Route::put('perfil/testimonio', [PatientTestimonialController::class, 'update'])->name('patient.testimonial.update');

    // Citas del paciente: siempre sobre Auth::user(), sin route-model-binding
    // de otro usuario (cancel() sí recibe {appointment} porque el paciente
    // puede tener varias, pero verifica la propiedad explícitamente).
    Route::get('perfil/citas', [AppointmentBookingController::class, 'index'])->name('patient.appointments.index');
    Route::post('perfil/citas', [AppointmentBookingController::class, 'store'])->name('patient.appointments.store');
    Route::delete('perfil/citas/{appointment}', [AppointmentBookingController::class, 'cancel'])->name('patient.appointments.cancel');
});
