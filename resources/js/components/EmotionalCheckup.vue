<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    questions: { type: Array, default: () => [] },
    options: { type: Array, default: () => [] },
    period: { type: String, default: '' },
    disclaimer: { type: String, default: '' },
    endpoint: { type: String, required: true },
    whatsapp: { type: String, default: '' },
});

const answers = ref(props.questions.map(() => null));
const email = ref('');
const website = ref(''); // honeypot
const loading = ref(false);
const result = ref(null);
const error = ref(null);

const allAnswered = computed(() => answers.value.every((a) => a !== null));

const bandStyle = computed(() => {
    if (!result.value) return '';
    return {
        bajo: 'border-sage-200 bg-sage-50',
        medio: 'border-clay-400/40 bg-cream-100',
        alto: 'border-clay-500/50 bg-cream-100',
    }[result.value.band];
});

async function submit() {
    if (!allAnswered.value || loading.value) return;
    loading.value = true;
    error.value = null;
    try {
        const { data } = await window.axios.post(props.endpoint, {
            answers: answers.value.map(Number),
            email: email.value || null,
            website: website.value,
        });
        result.value = data;
    } catch (e) {
        error.value = 'No se pudo enviar el chequeo. Inténtalo de nuevo en un momento.';
    } finally {
        loading.value = false;
    }
}

function reset() {
    answers.value = props.questions.map(() => null);
    email.value = '';
    result.value = null;
    error.value = null;
}
</script>

<template>
    <div class="rounded-3xl border border-cream-200 bg-white p-6 sm:p-9">
        <template v-if="!result">
            <p class="mb-6 text-sm font-semibold text-sage-500">{{ props.period }}</p>

            <div class="space-y-7">
                <fieldset v-for="(q, i) in props.questions" :key="i">
                    <legend class="mb-3 font-serif text-lg text-sage-800">{{ i + 1 }}. {{ q }}</legend>
                    <div class="flex flex-wrap gap-2">
                        <label
                            v-for="(opt, oi) in props.options"
                            :key="oi"
                            class="cursor-pointer rounded-full border px-4 py-2 text-sm font-semibold transition-colors"
                            :class="answers[i] === oi
                                ? 'border-sage-600 bg-sage-600 text-cream-50'
                                : 'border-cream-200 bg-cream-50 text-ink-soft hover:border-sage-300'"
                        >
                            <input type="radio" class="sr-only" :name="`q${i}`" :value="oi" v-model="answers[i]" />
                            {{ opt }}
                        </label>
                    </div>
                </fieldset>
            </div>

            <input v-model="website" type="text" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true" />

            <div class="mt-8">
                <label class="mb-1.5 block text-sm font-semibold text-sage-700">
                    Email (opcional, solo si quieres que te escriba)
                </label>
                <input
                    v-model="email"
                    type="email"
                    placeholder="tu@email.com"
                    class="w-full rounded-xl border border-cream-200 bg-cream-50 px-4 py-3 text-sm outline-none focus:border-sage-400"
                />
            </div>

            <p v-if="error" class="mt-4 text-sm font-semibold text-clay-500">{{ error }}</p>

            <button
                type="button"
                class="btn btn-primary mt-6 w-full sm:w-auto"
                :disabled="!allAnswered || loading"
                :class="{ 'opacity-50': !allAnswered || loading }"
                @click="submit"
            >
                {{ loading ? 'Enviando…' : 'Ver mi resultado' }}
            </button>
        </template>

        <template v-else>
            <div class="rounded-2xl border p-6" :class="bandStyle">
                <p class="eyebrow mb-2">Tu resultado orientativo</p>
                <div class="mb-4 flex items-baseline gap-2">
                    <span class="font-serif text-4xl text-sage-800">{{ result.score }}</span>
                    <span class="text-sm text-ink-soft">de {{ result.max }}</span>
                </div>
                <h3 class="mb-2 text-xl">{{ result.result.title }}</h3>
                <p class="leading-relaxed text-ink-soft">{{ result.result.text }}</p>
            </div>

            <div class="mt-6 flex flex-wrap gap-3">
                <a
                    v-if="props.whatsapp"
                    :href="props.whatsapp"
                    target="_blank"
                    rel="noopener"
                    class="btn btn-primary"
                >
                    Escríbeme por WhatsApp
                </a>
                <button type="button" class="btn btn-ghost" @click="reset">Repetir el chequeo</button>
            </div>

            <p class="mt-6 text-xs leading-relaxed text-ink-soft">{{ props.disclaimer }}</p>
        </template>
    </div>
</template>
