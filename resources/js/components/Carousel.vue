<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    images: { type: Array, default: () => [] }, // [{ url, alt }]
    intervalMs: { type: Number, default: 6000 },
    // Proporción de cada imagen, como clase de Tailwind (p. ej. "aspect-[4/3]").
    // Se deja configurable para que quien use el carrusel decida cuánto
    // espacio ocupa, en vez de imponer un panorámico 16:9 fijo siempre.
    aspectClass: { type: String, default: 'aspect-[16/9]' },
});

const track = ref(null);
const active = ref(0);
const paused = ref(false);
let timer = null;
let scrollTimeout = null;

function goTo(i) {
    if (!track.value) return;
    const width = track.value.clientWidth;
    track.value.scrollTo({ left: width * i, behavior: 'smooth' });
}

function next() {
    goTo((active.value + 1) % props.images.length);
}

function prev() {
    goTo((active.value - 1 + props.images.length) % props.images.length);
}

function onScroll() {
    clearTimeout(scrollTimeout);
    scrollTimeout = setTimeout(() => {
        if (!track.value) return;
        const width = track.value.clientWidth || 1;
        active.value = Math.round(track.value.scrollLeft / width);
    }, 80);
}

function startAutoplay() {
    stopAutoplay();
    if (props.images.length <= 1) return;
    timer = setInterval(() => {
        if (!paused.value) next();
    }, props.intervalMs);
}

function stopAutoplay() {
    if (timer) clearInterval(timer);
    timer = null;
}

onMounted(startAutoplay);
onUnmounted(stopAutoplay);
</script>

<template>
    <div
        v-if="images.length"
        class="relative"
        @mouseenter="paused = true"
        @mouseleave="paused = false"
        @touchstart="paused = true"
        @touchend="paused = false"
    >
        <div
            ref="track"
            class="flex snap-x snap-mandatory overflow-x-auto scroll-smooth rounded-3xl"
            style="scrollbar-width: none;"
            @scroll="onScroll"
        >
            <div
                v-for="(image, i) in images"
                :key="image.url + i"
                class="w-full shrink-0 snap-center"
                :class="aspectClass"
            >
                <img :src="image.url" :alt="image.alt" class="h-full w-full object-cover" loading="lazy">
            </div>
        </div>

        <template v-if="images.length > 1">
            <button
                type="button"
                class="absolute left-3 top-1/2 grid size-10 -translate-y-1/2 place-items-center rounded-full bg-white/90 text-sky-700 shadow-md backdrop-blur hover:bg-white sm:left-4"
                aria-label="Imagen anterior"
                @click="prev"
            >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M15 5 8 12l7 7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
            <button
                type="button"
                class="absolute right-3 top-1/2 grid size-10 -translate-y-1/2 place-items-center rounded-full bg-white/90 text-sky-700 shadow-md backdrop-blur hover:bg-white sm:right-4"
                aria-label="Imagen siguiente"
                @click="next"
            >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="m9 5 7 7-7 7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>

            <div class="absolute bottom-3 left-1/2 flex -translate-x-1/2 gap-2 sm:bottom-4">
                <button
                    v-for="(image, i) in images"
                    :key="'dot-' + i"
                    type="button"
                    class="size-2 rounded-full transition-all"
                    :class="active === i ? 'w-5 bg-white' : 'bg-white/60 hover:bg-white/80'"
                    :aria-label="`Ir a la imagen ${i + 1}`"
                    @click="goTo(i)"
                ></button>
            </div>
        </template>
    </div>
</template>

<style scoped>
/* Oculta la barra de scroll horizontal en navegadores basados en WebKit;
   el swipe táctil nativo sigue funcionando igual gracias a scroll-snap. */
div::-webkit-scrollbar {
    display: none;
}
</style>
