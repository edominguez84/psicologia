<x-patient-layout title="Mi perfil">
    <h1 class="text-2xl font-serif text-sky-800">Mi perfil</h1>
    <p class="mt-2 text-sm text-ink-soft">
        Actualiza tus datos generales y tu foto de perfil.
    </p>

    <div class="mt-8 max-w-lg">
        <h2 class="mb-3 font-serif text-lg text-sky-800">Foto de perfil</h2>

        <div class="flex items-center gap-4">
            <div class="grid size-20 shrink-0 place-items-center overflow-hidden rounded-full border border-paper-200 bg-paper-100">
                @if ($user->avatar_path)
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($user->avatar_path) }}" alt="{{ $user->name }}" class="size-full object-cover">
                @else
                    <span class="text-2xl font-bold text-sky-700">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                @endif
            </div>

            <div class="flex-1 space-y-2">
                <form method="POST" action="{{ route('patient.profile.photo.update') }}" enctype="multipart/form-data" class="flex flex-wrap items-center gap-2">
                    @csrf
                    <input type="file" name="photo" accept="image/*" required
                        class="block text-sm file:mr-3 file:rounded-full file:border-0 file:bg-sky-100 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-sky-700">
                    <button type="submit" class="btn btn-primary !px-4 !py-2 text-sm">Subir</button>
                </form>
                <p class="text-xs text-ink-soft">Formato PNG o JPG, máximo 2&nbsp;MB, hasta 2000×2000&nbsp;px.</p>
                @error('photo')<p class="text-xs font-semibold text-clay-500">{{ $message }}</p>@enderror

                @if ($user->avatar_path)
                    <form method="POST" action="{{ route('patient.profile.photo.destroy') }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-xs font-semibold text-clay-500 hover:underline">Quitar foto</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <div class="mt-10 max-w-lg">
        <h2 class="mb-3 font-serif text-lg text-sky-800">Datos generales</h2>
        <form method="POST" action="{{ route('patient.profile.update') }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div>
                <x-input-label for="name" value="Nombre completo" />
                <x-text-input id="name" type="text" name="name" :value="old('name', $user->name)" required />
                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="email" value="Email" />
                <x-text-input id="email" type="email" :value="$user->email" disabled class="bg-paper-100 text-ink-soft" />
                <p class="mt-1 text-xs text-ink-soft">El correo no se puede cambiar desde aquí.</p>
            </div>

            <div>
                <x-input-label for="phone_number" value="Teléfono" />
                <x-text-input id="phone_number" type="tel" name="phone_number" :value="old('phone_number', $user->phone_number)" required />
                <x-input-error :messages="$errors->get('phone_number')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="birth_date" value="Fecha de nacimiento" />
                <x-text-input id="birth_date" type="date" name="birth_date" :value="old('birth_date', optional($user->birth_date)->format('Y-m-d'))" required />
                <x-input-error :messages="$errors->get('birth_date')" class="mt-1" />
            </div>

            <x-primary-button>Guardar cambios</x-primary-button>
        </form>
    </div>
</x-patient-layout>
