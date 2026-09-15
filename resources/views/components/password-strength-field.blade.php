@props(['name' => 'password', 'label' => 'Contraseña', 'required' => true, 'autocomplete' => 'new-password'])

{{--
    Campo de contraseña con medidor de fuerza en vivo (Alpine, sin
    dependencias externas): barra de progreso + checklist de los 5 criterios
    que exige el backend (Password::min(10)->mixedCase()->numbers()->symbols(),
    ver AppServiceProvider::boot()). Reusado en el registro público y en el
    alta manual de staff — cualquier formulario nuevo que cree una cuenta
    debería usar este mismo componente en vez de un <x-text-input> suelto.
--}}
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
    <x-input-label for="{{ $name }}" :value="$label" />
    <x-text-input
        id="{{ $name }}" type="password" name="{{ $name }}" :required="$required" :autocomplete="$autocomplete"
        x-model="password"
    />
    <x-input-error :messages="$errors->get($name)" class="mt-1" />

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
