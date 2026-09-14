<x-admin-layout title="Métodos de pago">
    <h1 class="text-2xl font-serif text-sky-800">Métodos de pago</h1>
    <p class="mt-2 text-sm text-ink-soft">
        Elige cómo pagarán las pacientes sus citas. Puedes dejar lista la configuración de Wompi
        para cuando tengas tus credenciales, y mientras tanto usar transferencia bancaria.
    </p>

    <form method="POST" action="{{ route('admin.payment-settings.update') }}" enctype="multipart/form-data" class="mt-8 max-w-xl space-y-8" x-data="{ method: '{{ old('method', $payment['method']) }}' }">
        @csrf
        @method('PUT')

        <div>
            <h2 class="mb-3 font-serif text-lg text-sky-800">Método activo de cara a la paciente</h2>
            <div class="space-y-2">
                <label class="flex items-center gap-3 rounded-xl border border-paper-200 bg-paper-50 px-4 py-3 text-sm">
                    <input type="radio" name="method" value="bank_transfer" x-model="method" class="accent-sky-600">
                    Transferencia bancaria
                </label>
                <label class="flex items-center gap-3 rounded-xl border border-paper-200 bg-paper-50 px-4 py-3 text-sm">
                    <input type="radio" name="method" value="wompi" x-model="method" class="accent-sky-600">
                    Wompi
                </label>
            </div>
            @error('method')<p class="mt-1 text-xs font-semibold text-clay-500">{{ $message }}</p>@enderror
        </div>

        <div class="space-y-4 border-t border-paper-200 pt-6" x-show="method === 'bank_transfer'" x-cloak>
            <h2 class="font-serif text-lg text-sky-800">Transferencia bancaria</h2>

            <div>
                <label for="bank_name" class="mb-1.5 block text-sm font-semibold text-sky-700">Banco</label>
                <input type="text" name="bank_name" id="bank_name" value="{{ old('bank_name', $payment['bank_transfer']['bank_name']) }}"
                    class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400">
            </div>

            <div>
                <label for="account_number" class="mb-1.5 block text-sm font-semibold text-sky-700">Número de cuenta</label>
                <input type="text" name="account_number" id="account_number" value="{{ old('account_number', $payment['bank_transfer']['account_number']) }}"
                    class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400">
            </div>

            <div>
                <label for="account_holder" class="mb-1.5 block text-sm font-semibold text-sky-700">Titular de la cuenta</label>
                <input type="text" name="account_holder" id="account_holder" value="{{ old('account_holder', $payment['bank_transfer']['account_holder']) }}"
                    class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400">
            </div>

            <div>
                <label for="instructions" class="mb-1.5 block text-sm font-semibold text-sky-700">Instrucciones para la paciente</label>
                <textarea name="instructions" id="instructions" rows="3" maxlength="1000"
                    class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400">{{ old('instructions', $payment['bank_transfer']['instructions']) }}</textarea>
            </div>

            @if (! empty($payment['bank_transfer']['account_image_path']))
                <div>
                    <p class="mb-2 text-sm font-semibold text-sky-700">Imagen actual del número de cuenta</p>
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($payment['bank_transfer']['account_image_path']) }}" alt="Número de cuenta" class="max-w-xs rounded-xl border border-paper-200">
                </div>
            @endif

            <div>
                <label for="account_image" class="mb-1.5 block text-sm font-semibold text-sky-700">
                    {{ !empty($payment['bank_transfer']['account_image_path']) ? 'Reemplazar imagen del número de cuenta' : 'Imagen del número de cuenta (opcional)' }}
                </label>
                <input type="file" name="account_image" id="account_image" accept="image/png,image/jpeg"
                    class="block w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm file:mr-4 file:rounded-full file:border-0 file:bg-sky-100 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-sky-700">
                <p class="mt-1 text-xs text-ink-soft">Formato PNG o JPG, máximo 2&nbsp;MB, hasta 2000×2000&nbsp;px.</p>
                @error('account_image')<p class="mt-1 text-xs font-semibold text-clay-500">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="space-y-4 border-t border-paper-200 pt-6" x-show="method === 'wompi'" x-cloak>
            <h2 class="font-serif text-lg text-sky-800">Credenciales de Wompi</h2>
            <p class="text-sm text-ink-soft">
                Guarda aquí tus credenciales cuando las tengas. El cobro real todavía no está
                conectado — esta pantalla solo deja lista la configuración.
            </p>

            <div>
                <label for="wompi_public_key" class="mb-1.5 block text-sm font-semibold text-sky-700">Llave pública</label>
                <input type="text" name="wompi_public_key" id="wompi_public_key" value="{{ old('wompi_public_key', $payment['wompi']['public_key']) }}"
                    class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400" autocomplete="off">
            </div>

            <div>
                <label for="wompi_private_key" class="mb-1.5 block text-sm font-semibold text-sky-700">Llave privada</label>
                <input type="password" name="wompi_private_key" id="wompi_private_key" value="{{ old('wompi_private_key', $payment['wompi']['private_key']) }}"
                    class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400" autocomplete="off">
            </div>

            <div>
                <label for="wompi_events_key" class="mb-1.5 block text-sm font-semibold text-sky-700">Llave de eventos</label>
                <input type="password" name="wompi_events_key" id="wompi_events_key" value="{{ old('wompi_events_key', $payment['wompi']['events_key']) }}"
                    class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400" autocomplete="off">
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Guardar configuración de pagos</button>
    </form>
</x-admin-layout>
