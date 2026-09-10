<script setup>
import { ref, watch, onUnmounted } from 'vue';

const props = defineProps({
    links: { type: Array, default: () => [] },
    whatsapp: { type: String, default: '' },
    cta: { type: String, default: 'Escríbeme' },
});

const open = ref(false);

function close() {
    open.value = false;
}

// Bloquea el scroll del fondo mientras el menú está abierto.
watch(open, (value) => {
    document.documentElement.style.overflow = value ? 'hidden' : '';
});

onUnmounted(() => {
    document.documentElement.style.overflow = '';
});
</script>

<template>
    <div class="md:hidden">
        <button
            type="button"
            class="relative z-[60] grid size-10 place-items-center rounded-full border border-sage-200 text-sage-700"
            :aria-expanded="open"
            aria-label="Abrir menú"
            @click="open = !open"
        >
            <svg v-if="!open" width="18" height="18" viewBox="0 0 18 18" fill="none">
                <path d="M2 4h14M2 9h14M2 14h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
            </svg>
            <svg v-else width="18" height="18" viewBox="0 0 18 18" fill="none">
                <path d="M4 4l10 10M14 4L4 14" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
            </svg>
        </button>

        <Teleport to="body">
            <Transition name="fade">
                <div
                    v-if="open"
                    class="fixed inset-0 z-50 flex flex-col overflow-y-auto bg-cream-50 px-5 pb-8 pt-20"
                >
                    <button
                        type="button"
                        class="absolute right-5 top-4 grid size-10 place-items-center rounded-full border border-sage-200 text-sage-700"
                        aria-label="Cerrar menú"
                        @click="close"
                    >
                        <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
                            <path d="M4 4l10 10M14 4L4 14" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                        </svg>
                    </button>

                    <nav class="flex flex-col gap-1">
                        <a
                            v-for="link in props.links"
                            :key="link.href"
                            :href="link.href"
                            class="rounded-xl px-3 py-3 font-serif text-xl text-sage-800 hover:bg-sage-50"
                            @click="close"
                        >
                            {{ link.label }}
                        </a>
                    </nav>

                    <a
                        :href="props.whatsapp"
                        target="_blank"
                        rel="noopener"
                        class="btn btn-primary mt-6 w-full"
                        @click="close"
                    >
                        {{ props.cta }}
                    </a>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>
