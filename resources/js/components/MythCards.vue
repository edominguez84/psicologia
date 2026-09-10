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
            class="group relative min-h-44 rounded-2xl border p-6 text-left transition-colors"
            :class="flipped.has(i)
                ? 'border-sage-300 bg-sage-600 text-cream-50'
                : 'border-cream-200 bg-white hover:border-sage-200'"
            @click="toggle(i)"
        >
            <span
                class="eyebrow mb-3 block"
                :class="flipped.has(i) ? 'text-cream-200' : 'text-clay-500'"
            >
                {{ flipped.has(i) ? 'La realidad' : 'El mito' }}
            </span>
            <p
                class="font-serif text-lg leading-snug"
                :class="flipped.has(i) ? 'text-cream-50' : 'text-sage-800'"
            >
                {{ flipped.has(i) ? item.truth : item.myth }}
            </p>
            <span
                class="absolute bottom-4 right-5 text-xs font-bold"
                :class="flipped.has(i) ? 'text-cream-200/80' : 'text-sage-400'"
            >
                Tocar para girar
            </span>
        </button>
    </div>
</template>
