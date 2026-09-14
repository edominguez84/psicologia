/**
 * Modo claro / oscuro / sistema, elegido por cada persona en su propio
 * navegador (no es una preferencia de cuenta ni una configuración global del
 * sitio). Se aplica como clase "dark" en <html>, que Tailwind v4 usa vía
 * @custom-variant dark (ver resources/css/app.css).
 *
 * Este módulo se importa desde app.js, pero window.setThemeMode también se
 * expone para que el switch (resources/views/partials/theme-switch.blade.php)
 * pueda llamarlo directamente sin depender de Alpine para la lógica en sí.
 */
const STORAGE_KEY = 'themeMode'; // 'light' | 'dark' | 'system'

function resolveIsDark(mode) {
    if (mode === 'dark') return true;
    if (mode === 'light') return false;

    return window.matchMedia('(prefers-color-scheme: dark)').matches;
}

function applyMode(mode) {
    document.documentElement.classList.toggle('dark', resolveIsDark(mode));
}

export function getThemeMode() {
    try {
        return localStorage.getItem(STORAGE_KEY) ?? 'system';
    } catch (e) {
        return 'system';
    }
}

export function setThemeMode(mode) {
    try {
        localStorage.setItem(STORAGE_KEY, mode);
    } catch (e) {
        // localStorage no disponible: el modo se aplica igual para esta
        // carga de página, solo no persiste entre recargas.
    }
    applyMode(mode);
}

export function initThemeMode() {
    applyMode(getThemeMode());

    // Si el modo activo es "sistema", reacciona en vivo a que la persona
    // cambie el tema de su sistema operativo sin necesidad de recargar.
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        if (getThemeMode() === 'system') {
            applyMode('system');
        }
    });
}

window.setThemeMode = setThemeMode;
window.getThemeMode = getThemeMode;
