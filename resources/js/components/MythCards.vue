<script setup>
import { ref } from 'vue';

const props = defineProps({
    items: { type: Array, default: () => [] },
});

const flipped = ref(new Set());

function toggle(i) {
    const next = new Set(flipped.value);
    next.has(i) ? next.delete(i) : next.add(i);
    flipped.value = next;
}
</script>

<template>
    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        <button
            v-for="(item, i) in props.items"
            :key="i"
            type="button"
            class="group flex min-h-40 flex-col rounded-2xl border p-6 text-left transition-colors"
            :class="flipped.has(i)
                ? 'border-sky-300 bg-sky-600 text-paper-50'
                : 'border-paper-200 bg-white hover:border-sky-200'"
            @click="toggle(i)"
        >
            <span
                class="eyebrow mb-3 block"
                :class="flipped.has(i) ? 'text-paper-200' : 'text-clay-500'"
            >
                {{ flipped.has(i) ? 'La realidad' : 'El mito' }}
            </span>
            <p
                class="grow font-serif text-lg leading-snug"
                :class="flipped.has(i) ? 'text-paper-50' : 'text-sky-800'"
            >
                {{ flipped.has(i) ? item.truth : item.myth }}
            </p>
            <span
                class="mt-4 self-end text-xs font-bold"
                :class="flipped.has(i) ? 'text-paper-200/80' : 'text-sky-400'"
            >
                Tocar para girar
            </span>
        </button>
    </div>
</template>
