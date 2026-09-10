<script setup>
import { ref } from 'vue';

const props = defineProps({
    links: { type: Array, default: () => [] },
    whatsapp: { type: String, default: '' },
    cta: { type: String, default: 'Escríbeme' },
});

const open = ref(false);

function close() {
    open.value = false;
}
</script>

<template>
    <div class="md:hidden">
        <button
            type="button"
            class="grid size-10 place-items-center rounded-full border border-sage-200 text-sage-700"
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

        <Transition name="fade">
            <div v-if="open" class="fixed inset-0 top-16 z-40 bg-cream-50 px-5 py-6">
                <nav class="flex flex-col gap-1">
                    <a
                        v-for="link in props.links"
                        :key="link.href"
                        :href="link.href"
                        class="rounded-xl px-3 py-3 font-serif text-lg text-sage-800 hover:bg-sage-50"
                        @click="close"
                    >
                        {{ link.label }}
                    </a>
                </nav>
                <a
                    :href="props.whatsapp"
                    target="_blank"
                    rel="noopener"
                    class="btn btn-primary mt-5 w-full"
                    @click="close"
                >
                    {{ props.cta }}
                </a>
            </div>
        </Transition>
    </div>
</template>
