{{--
    Aviso de cierre de sesión por inactividad, con cuenta regresiva de 30
    segundos para seguir conectado. Ver resources/js/inactivity.js para la
    lógica completa; este partial solo monta el overlay + la tarjeta.
    Recibe $timeoutMinutes (minutos configurados en Admin > Seguridad).
--}}
<div
    x-data="inactivityWatcher({
        timeoutMinutes: {{ (int) $timeoutMinutes }},
        pingUrl: '{{ route('session.ping') }}',
        logoutUrl: '{{ route('logout') }}',
    })"
    x-cloak
>
    <div
        x-show="showWarning"
        x-transition.opacity
        class="fixed inset-0 z-[100] flex items-center justify-center bg-black/40 backdrop-blur-sm px-4"
        role="alertdialog"
        aria-modal="true"
        aria-labelledby="inactivity-modal-title"
    >
        <div class="w-full max-w-sm rounded-3xl border border-paper-200 bg-white p-6 text-center shadow-2xl">
            <h2 id="inactivity-modal-title" class="text-lg font-serif text-sky-800">¿Sigues ahí?</h2>
            <p class="mt-2 text-sm text-ink-soft">
                Tu sesión se cerrará por inactividad en
            </p>
            <p class="mt-3 text-4xl font-bold text-clay-500" x-text="secondsLeft"></p>
            <p class="text-xs text-ink-soft">segundos</p>

            <div class="mt-6 flex flex-col gap-2">
                <button type="button" @click="stayConnected()" class="btn btn-primary">
                    Seguir conectado
                </button>
                <button type="button" @click="forceLogout()" class="btn btn-ghost">
                    Cerrar sesión ahora
                </button>
            </div>
        </div>
    </div>
</div>
