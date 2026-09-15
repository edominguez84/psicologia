<x-guest-layout>
    <p class="mb-6 text-sm text-ink-soft">
        Crea tu cuenta de paciente para agendar citas, ver tus citas agendadas y dejar tu
        testimonio.
    </p>

    @php
        $locations = \App\Support\ElSalvadorLocations::all();
    @endphp

    <form
        method="POST"
        action="{{ route('register') }}"
        class="space-y-5"
        x-data="{
            locations: {{ Illuminate\Support\Js::from($locations) }},
            department: '{{ old('department') }}',
            municipality: '{{ old('municipality') }}',
            get municipalities() {
                return this.locations[this.department]?.municipalities ?? [];
            },
        }"
    >
        @csrf

        @if ($promotionId)
            <input type="hidden" name="promotion" value="{{ $promotionId }}">
        @endif

        <div>
            <x-input-label for="name" value="Nombre completo" />
            <x-text-input id="name" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="phone_number" value="Teléfono" />
            <x-text-input id="phone_number" type="tel" name="phone_number" :value="old('phone_number')" required autocomplete="tel" />
            <x-input-error :messages="$errors->get('phone_number')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="birth_date" value="Fecha de nacimiento" />
            <x-text-input id="birth_date" type="date" name="birth_date" :value="old('birth_date')" required />
            <x-input-error :messages="$errors->get('birth_date')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="sex" value="Sexo" />
            <select id="sex" name="sex" required class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400">
                <option value="" disabled @selected(old('sex') === null)>Selecciona una opción</option>
                <option value="female" @selected(old('sex') === 'female')>Femenino</option>
                <option value="male" @selected(old('sex') === 'male')>Masculino</option>
                <option value="other" @selected(old('sex') === 'other')>Otro</option>
            </select>
            <x-input-error :messages="$errors->get('sex')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="department" value="Departamento" />
            <select
                id="department"
                name="department"
                required
                x-model="department"
                @change="municipality = ''"
                class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400"
            >
                <option value="" disabled>Selecciona un departamento</option>
                @foreach ($locations as $key => $department)
                    <option value="{{ $key }}">{{ $department['label'] }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('department')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="municipality" value="Municipio" />
            <select
                id="municipality"
                name="municipality"
                required
                x-model="municipality"
                :disabled="!department"
                class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400 disabled:cursor-not-allowed disabled:opacity-60"
            >
                <option value="" disabled>{{ old('department') ? 'Selecciona un municipio' : 'Primero elige un departamento' }}</option>
                <template x-for="m in municipalities" :key="m">
                    <option :value="m" x-text="m" :selected="m === municipality"></option>
                </template>
            </select>
            <x-input-error :messages="$errors->get('municipality')" class="mt-1" />
        </div>

        <x-password-strength-field name="password" label="Contraseña" />

        <div>
            <x-input-label for="password_confirmation" value="Confirmar contraseña" />
            <x-text-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1" />
        </div>

        <div class="flex items-center justify-between gap-4">
            <a class="text-sm font-semibold text-sky-700 underline" href="{{ route('login') }}">
                ¿Ya tienes cuenta? Inicia sesión
            </a>

            <x-primary-button>Crear cuenta</x-primary-button>
        </div>
    </form>
</x-guest-layout>
