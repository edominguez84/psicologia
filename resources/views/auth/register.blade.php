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

        <div
            x-data="{
                password: '',
                get checks() {
                    return {
                        length: this.password.length >= 10,
                        upper: /[A-Z]/.test(this.password),
                        lower: /[a-z]/.test(this.password),
                        number: /[0-9]/.test(this.password),
                        symbol: /[^A-Za-z0-9]/.test(this.password),
                    };
                },
                get score() {
                    return Object.values(this.checks).filter(Boolean).length;
                },
                get label() {
                    if (!this.password) return '';
                    return ['Muy débil', 'Muy débil', 'Débil', 'Aceptable', 'Fuerte', 'Muy fuerte'][this.score];
                },
                get barColor() {
                    if (this.score <= 2) return 'bg-clay-500';
                    if (this.score <= 3) return 'bg-amber-500';
                    if (this.score === 4) return 'bg-sky-500';
                    return 'bg-emerald-500';
                },
            }"
        >
            <x-input-label for="password" value="Contraseña" />
            <x-text-input
                id="password" type="password" name="password" required autocomplete="new-password"
                x-model="password"
            />
            <x-input-error :messages="$errors->get('password')" class="mt-1" />

            <div class="mt-2" x-show="password.length > 0" x-cloak>
                <div class="h-1.5 w-full overflow-hidden rounded-full bg-paper-200">
                    <div
                        class="h-full rounded-full transition-all duration-200"
                        :class="barColor"
                        :style="`width: ${(score / 5) * 100}%`"
                    ></div>
                </div>
                <p class="mt-1 text-xs font-semibold" :class="barColor.replace('bg-', 'text-')" x-text="label"></p>

                <ul class="mt-2 grid grid-cols-1 gap-1 text-xs text-ink-soft sm:grid-cols-2">
                    <li class="flex items-center gap-1.5" :class="checks.length ? 'text-emerald-600' : 'text-ink-soft'">
                        <span x-text="checks.length ? '✓' : '·'"></span> Mínimo 10 caracteres
                    </li>
                    <li class="flex items-center gap-1.5" :class="checks.upper ? 'text-emerald-600' : 'text-ink-soft'">
                        <span x-text="checks.upper ? '✓' : '·'"></span> Una mayúscula
                    </li>
                    <li class="flex items-center gap-1.5" :class="checks.lower ? 'text-emerald-600' : 'text-ink-soft'">
                        <span x-text="checks.lower ? '✓' : '·'"></span> Una minúscula
                    </li>
                    <li class="flex items-center gap-1.5" :class="checks.number ? 'text-emerald-600' : 'text-ink-soft'">
                        <span x-text="checks.number ? '✓' : '·'"></span> Un número
                    </li>
                    <li class="flex items-center gap-1.5 sm:col-span-2" :class="checks.symbol ? 'text-emerald-600' : 'text-ink-soft'">
                        <span x-text="checks.symbol ? '✓' : '·'"></span> Un carácter especial (ej. ! @ # $ %)
                    </li>
                </ul>
            </div>
        </div>

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
