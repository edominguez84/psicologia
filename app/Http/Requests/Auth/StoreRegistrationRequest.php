<?php

namespace App\Http\Requests\Auth;

use App\Support\ElSalvadorLocations;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone_number' => ['required', 'string', 'max:40'],
            'birth_date' => ['required', 'date', 'before:today'],
            'sex' => ['required', Rule::in(['male', 'female', 'other'])],
            'department' => ['required', Rule::in(array_keys(ElSalvadorLocations::all()))],
            'municipality' => ['required', 'string'],
            // Mínimo 10 caracteres, con mayúscula, minúscula, número y
            // símbolo — mismo criterio que el medidor de fuerza que ve la
            // persona en el formulario (resources/js/password-strength.js),
            // para que el backend nunca acepte algo que el frontend ya
            // habría marcado como "débil".
            'password' => ['required', 'confirmed', Password::min(10)->mixedCase()->numbers()->symbols()],
        ];
    }

    public function messages(): array
    {
        return [
            'sex.required' => 'Selecciona el sexo.',
            'sex.in' => 'El sexo seleccionado no es válido.',
            'department.required' => 'Selecciona un departamento.',
            'department.in' => 'El departamento seleccionado no es válido.',
            'municipality.required' => 'Selecciona un municipio.',
        ];
    }

    /**
     * El municipio se valida contra los que realmente pertenecen al
     * departamento elegido — así alguien manipulando el <select> a mano no
     * puede combinar un departamento con un municipio de otro.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $department = $this->string('department')->value();
            $municipality = $this->string('municipality')->value();

            if (! $department || ! $municipality) {
                return;
            }

            $validMunicipalities = ElSalvadorLocations::municipalitiesFor($department);

            if (! in_array($municipality, $validMunicipalities, true)) {
                $validator->errors()->add('municipality', 'Ese municipio no pertenece al departamento seleccionado.');
            }
        });
    }
}
