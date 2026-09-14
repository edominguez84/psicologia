/**
 * Cierre de sesión automático por inactividad, con aviso previo de 30
 * segundos para seguir conectado. Solo se activa si hay un usuario
 * autenticado (el nodo raíz con x-data="inactivityWatcher(...)" solo se
 * renderiza dentro de @auth en los layouts). El límite real también se
 * aplica en el servidor (App\Http\Middleware\EnsureSessionIsActive) como
 * defensa en profundidad, por si JavaScript está deshabilitado.
 *
 * Sincronizado entre pestañas del mismo navegador vía localStorage: la
 * actividad en cualquier pestaña reinicia el reloj de todas.
 */
export default function inactivityWatcher({ timeoutMinutes, pingUrl, logoutUrl }) {
    const WARNING_SECONDS = 30;
    const STORAGE_KEY = 'lastActivityAt';
    const timeoutMs = Math.max(1, timeoutMinutes) * 60 * 1000;

    return {
        showWarning: false,
        secondsLeft: WARNING_SECONDS,
        warningTimer: null,
        countdownTimer: null,
        activityThrottle: null,

        init() {
            this.recordActivity();
            this.scheduleWarning();

            ['mousemove', 'keydown', 'click', 'scroll', 'touchstart'].forEach((evt) => {
                window.addEventListener(evt, () => this.onActivity(), { passive: true });
            });

            window.addEventListener('storage', (e) => {
                if (e.key === STORAGE_KEY && !this.showWarning) {
                    this.scheduleWarning();
                }
            });
        },

        onActivity() {
            if (this.showWarning) return; // ya se está avisando: solo "Seguir conectado" cuenta.
            if (this.activityThrottle) return;

            this.activityThrottle = setTimeout(() => {
                this.activityThrottle = null;
            }, 5000);

            this.recordActivity();
            this.scheduleWarning();
        },

        recordActivity() {
            try {
                localStorage.setItem(STORAGE_KEY, String(Date.now()));
            } catch (e) {
                // localStorage no disponible (modo privado estricto, etc.):
                // el aviso sigue funcionando por pestaña, solo no se
                // sincroniza entre pestañas.
            }
        },

        scheduleWarning() {
            clearTimeout(this.warningTimer);
            clearInterval(this.countdownTimer);
            this.showWarning = false;

            this.warningTimer = setTimeout(() => {
                this.openWarning();
            }, timeoutMs - WARNING_SECONDS * 1000);
        },

        openWarning() {
            this.showWarning = true;
            this.secondsLeft = WARNING_SECONDS;

            this.countdownTimer = setInterval(() => {
                this.secondsLeft -= 1;
                if (this.secondsLeft <= 0) {
                    clearInterval(this.countdownTimer);
                    this.forceLogout();
                }
            }, 1000);
        },

        async stayConnected() {
            clearInterval(this.countdownTimer);
            this.showWarning = false;
            this.recordActivity();
            this.scheduleWarning();

            try {
                await fetch(pingUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        Accept: 'application/json',
                    },
                });
            } catch (e) {
                // Si el ping falla (red caída, etc.) el reloj del cliente ya
                // se reinició igual; el middleware del servidor decidirá en
                // la siguiente petición real si la sesión sigue viva.
            }
        },

        forceLogout() {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = logoutUrl;
            form.style.display = 'none';

            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
            form.appendChild(csrf);

            document.body.appendChild(form);
            form.submit();
        },
    };
}
