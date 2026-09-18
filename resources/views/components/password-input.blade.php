@props(['disabled' => false])

{{--
    Campo de contraseña/credencial con botón para mostrar/ocultar lo escrito
    (el "ojito"), antes de guardar — usado en los formularios de credenciales
    del admin (VAPI, Wompi, Telegram, WhatsApp, Facebook, Anthropic). El
    input en sí conserva exactamente la misma clase que ya usaban los 6
    campos sueltos (ver components/text-input.blade.php), solo se le agrega
    padding a la derecha para que el botón no tape el texto.
--}}
<div x-data="{ show: false }" class="relative">
    <input
        :type="show ? 'text' : 'password'"
        @disabled($disabled)
        {{ $attributes->merge(['class' => 'w-full rounded-xl border border-paper-200 bg-paper-50 py-2.5 pl-4 pr-11 text-sm outline-none focus:border-sky-400']) }}
    >
    <button
        type="button"
        @click="show = !show"
        class="absolute inset-y-0 right-0 grid w-11 place-items-center text-ink-soft hover:text-sky-700"
        :aria-label="show ? 'Ocultar' : 'Mostrar'"
        tabindex="-1"
    >
        <span x-show="!show">@include('partials.admin-nav-icon', ['name' => 'eye'])</span>
        <span x-show="show" x-cloak>@include('partials.admin-nav-icon', ['name' => 'eye-off'])</span>
    </button>
</div>
