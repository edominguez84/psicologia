import './bootstrap';
import { createApp } from 'vue';
import Alpine from 'alpinejs';

// Editor de texto enriquecido para las páginas legales del panel de admin
// (Política de privacidad, Condiciones de uso). Es un web component nativo
// (<trix-editor>), no requiere montarse manualmente: se activa solo si
// encuentra un <trix-editor> en la página.
import 'trix';
import 'trix/dist/trix.css';

import inactivityWatcher from './inactivity';
import { initThemeMode } from './theme-mode';

import FaqAccordion from './components/FaqAccordion.vue';
import MythCards from './components/MythCards.vue';
import EmotionalCheckup from './components/EmotionalCheckup.vue';
import ContactForm from './components/ContactForm.vue';
import MobileNav from './components/MobileNav.vue';
import ScheduleCallModal from './components/ScheduleCallModal.vue';
import Carousel from './components/Carousel.vue';
import ChatbotWidget from './components/ChatbotWidget.vue';

// Alpine.js se usa tanto en el panel de administración (repetidores de
// formularios dinámicos, aviso de inactividad) como en el sitio público
// (switch de tema, aviso de inactividad, menú móvil vía Vue aparte); Vue
// sigue siendo el motor de las islas interactivas más complejas.
window.Alpine = Alpine;
Alpine.data('inactivityWatcher', inactivityWatcher);
Alpine.start();

initThemeMode();

/**
 * Monta un componente Vue en cada elemento que tenga el atributo
 * data-vue="<nombre>", pasándole como props el contenido de data-props (JSON).
 */
const registry = {
    FaqAccordion,
    MythCards,
    EmotionalCheckup,
    ContactForm,
    MobileNav,
    ScheduleCallModal,
    Carousel,
    ChatbotWidget,
};

document.querySelectorAll('[data-vue]').forEach((el) => {
    const name = el.dataset.vue;
    const component = registry[name];
    if (!component) return;

    let props = {};
    if (el.dataset.props) {
        try {
            props = JSON.parse(el.dataset.props);
        } catch (e) {
            console.error(`Props inválidas para ${name}`, e);
        }
    }

    createApp(component, props).mount(el);
});

/**
 * Animación de aparición al hacer scroll (progressive enhancement): cualquier
 * elemento con clase "reveal" empieza oculto/desplazado vía CSS y gana
 * "is-visible" cuando entra en el viewport. Si el navegador no soporta
 * IntersectionObserver, se revela todo de inmediato para no esconder
 * contenido de forma permanente.
 */
const revealTargets = document.querySelectorAll('.reveal');
if (revealTargets.length) {
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    }
                });
            },
            { threshold: 0.15, rootMargin: '0px 0px -40px 0px' }
        );
        revealTargets.forEach((el) => observer.observe(el));
    } else {
        revealTargets.forEach((el) => el.classList.add('is-visible'));
    }
}
