<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Password;

class StoreStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La protección real es el middleware 'super_admin' en la ruta, no
        // el conjunto de roles elegibles aquí: quien llena este formulario
        // ya es super_admin, así que puede asignar cualquier rol del enum.
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            // Mismo criterio que el registro público (StoreRegistrationRequest):
            // mínimo 10 caracteres, mayúscula, minúscula, número y símbolo.
            'password' => ['required', Password::min(10)->mixedCase()->numbers()->symbols()],
            'role' => ['required', new Enum(UserRole::class)],
        ];
    }
}
