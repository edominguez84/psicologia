<x-guest-layout>
    <div class="mb-4 text-sm text-ink-soft">
        Antes de continuar, confirma tu email haciendo clic en el enlace que te acabamos de enviar.
        Si no lo recibiste, con gusto te enviamos otro.
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 text-sm font-semibold text-sky-700">
            Se ha enviado un nuevo enlace de verificación al email indicado.
        </div>
    @endif

    <div class="flex items-center justify-between gap-4">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-primary-button>Reenviar email de verificación</x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm font-semibold text-sky-700 underline">Cerrar sesión</button>
        </form>
    </div>
</x-guest-layout>
