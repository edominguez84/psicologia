<script setup>
import { ref, watch, onUnmounted } from 'vue';
import ContactForm from './ContactForm.vue';

const props = defineProps({
    subjects: { type: Array, default: () => [] },
    endpoint: { type: String, default: null },
    demoMode: { type: Boolean, default: false },
    whatsapp: { type: String, default: '' },
    // Texto del asunto a preseleccionar en el formulario (normalmente el
    // primero de `subjects`, "Reservar llamada de 15 min").
    subject: { type: String, default: null },
    triggerLabel: { type: String, default: 'Reserva una llamada gratis de 15 min' },
    triggerClass: { type: String, default: 'btn btn-ghost' },
    visibleFields: { type: Object, default: () => ({}) },
    customFields: { type: Array, default: () => [] },
});

const open = ref(false);

function show() {
    open.value = true;
}

function close() {
    open.value = false;
}

watch(open, (value) => {
    document.documentElement.style.overflow = value ? 'hidden' : '';
});

onUnmounted(() => {
    document.documentElement.style.overflow = '';
});
</script>

<template>
    <button type="button" :class="triggerClass" @click="show">{{ triggerLabel }}</button>

    <Teleport to="body">
        <Transition name="fade">
            <div
                v-if="open"
                class="fixed inset-0 z-[100] flex items-start justify-center overflow-y-auto bg-ink/50 px-4 py-8 backdrop-blur-sm sm:items-center"
                @click.self="close"
            >
                <div class="w-full max-w-xl rounded-3xl bg-white shadow-2xl">
                    <div class="flex items-center justify-between border-b border-paper-200 px-6 py-5 sm:px-8">
                        <div>
                            <p class="eyebrow mb-1">Sin compromiso</p>
                            <h2 class="font-serif text-xl text-sky-800">Reserva tu llamada gratuita</h2>
                        </div>
                        <button
                            type="button"
                            class="grid size-9 shrink-0 place-items-center rounded-full text-ink-soft hover:bg-paper-100"
                            aria-label="Cerrar"
                            @click="close"
                        >
                            <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
                                <path d="M4 4l10 10M14 4L4 14" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                            </svg>
                        </button>
                    </div>

                    <div class="max-h-[75vh] overflow-y-auto px-6 py-6 sm:px-8">
                        <p class="mb-5 text-sm leading-relaxed text-ink-soft">
                            Cuéntame un poco sobre ti y te contacto para coordinar el horario de tu llamada de 15 minutos.
                        </p>
                        <ContactForm
                            :subjects="subjects"
                            :endpoint="endpoint"
                            :demo-mode="demoMode"
                            :whatsapp="whatsapp"
                            :initial-subject="subject"
                            :visible-fields="visibleFields"
                            :custom-fields="customFields"
                        />
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
