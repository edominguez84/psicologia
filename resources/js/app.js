import './bootstrap';
import { createApp } from 'vue';
import Alpine from 'alpinejs';

import FaqAccordion from './components/FaqAccordion.vue';
import MythCards from './components/MythCards.vue';
import EmotionalCheckup from './components/EmotionalCheckup.vue';
import ContactForm from './components/ContactForm.vue';
import MobileNav from './components/MobileNav.vue';
import ScheduleCallModal from './components/ScheduleCallModal.vue';
import Carousel from './components/Carousel.vue';

// Alpine.js se usa únicamente en el panel de administración (repetidores de
// formularios dinámicos); el sitio público sigue usando Vue para sus islas
// interactivas.
window.Alpine = Alpine;
Alpine.start();

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
