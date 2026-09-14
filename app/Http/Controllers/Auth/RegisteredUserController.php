<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StoreRegistrationRequest;
use App\Models\Promotion;
use App\Models\User;
use App\Services\TwoFactorChallengeService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Autoregistro público: SIEMPRE crea una cuenta de paciente. El campo
     * 'role' nunca se lee del request en ningún punto de este controlador —
     * ni de $request->all(), ni de validated() — para que no exista ninguna
     * superficie de mass-assignment de rol desde un formulario público.
     */
    public function create(Request $request): View
    {
        return view('auth.register', [
            'promotionId' => $request->integer('promotion') ?: null,
        ]);
    }

    public function store(StoreRegistrationRequest $request, TwoFactorChallengeService $twoFactor): RedirectResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone_number' => $data['phone_number'],
            'birth_date' => $data['birth_date'],
            'sex' => $data['sex'],
            'department' => $data['department'],
            'municipality' => $data['municipality'],
            'password' => Hash::make($data['password']),
            'role' => UserRole::Patient,
        ]);

        // Mismo flujo que el login normal: no se autentica todavía, se exige
        // el segundo factor primero (consistente con el resto del sitio,
        // donde el 2FA es obligatorio para toda cuenta, no solo para staff).
        $request->session()->regenerate();
        $request->session()->put('pending_2fa', [
            'user_id' => $user->id,
            'remember' => false,
        ]);

        // Si el registro vino desde una tarjeta de promoción, se guarda como
        // "url.intended" — el mismo mecanismo estándar que ya usa
        // redirect()->intended() tras el 2FA (ver TwoFactorChallengeController)
        // — así, tras confirmar el código, la persona llega directo a
        // solicitar su cita con esa promoción ya elegida, en vez de al perfil.
        if ($request->filled('promotion') && Promotion::active()->whereKey($request->integer('promotion'))->exists()) {
            $request->session()->put('url.intended', route('patient.appointments.index', ['promotion' => $request->integer('promotion')]));
        }

        $twoFactor->issueChallenge($user);

        return redirect()->route('2fa.challenge');
    }
}
