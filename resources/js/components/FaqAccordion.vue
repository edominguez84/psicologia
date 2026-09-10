<script setup>
import { ref } from 'vue';

const props = defineProps({
    items: { type: Array, default: () => [] },
});

const open = ref(null);

function toggle(i) {
    open.value = open.value === i ? null : i;
}
</script>

<template>
    <div class="divide-y divide-cream-200 rounded-2xl border border-cream-200 bg-white">
        <div v-for="(item, i) in props.items" :key="i">
            <button
                type="button"
                class="flex w-full items-center justify-between gap-4 px-5 py-5 text-left"
                :aria-expanded="open === i"
                @click="toggle(i)"
            >
                <span class="font-serif text-lg text-sage-800">{{ item.q }}</span>
                <span
                    class="grid size-7 shrink-0 place-items-center rounded-full bg-sage-100 text-sage-600 transition-transform"
                    :class="{ 'rotate-45': open === i }"
                >
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                        <path d="M7 1v12M1 7h12" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                    </svg>
                </span>
            </button>
            <div class="accordion-body" :class="{ open: open === i }">
                <div>
                    <p class="px-5 pb-6 leading-relaxed text-ink-soft">{{ item.a }}</p>
                </div>
            </div>
        </div>
    </div>
</template>
