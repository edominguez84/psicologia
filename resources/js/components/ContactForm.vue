<script setup>
import { reactive, ref } from 'vue';

const props = defineProps({
    subjects: { type: Array, default: () => [] },
    endpoint: { type: String, required: true },
});

const form = reactive({
    name: '',
    email: '',
    phone: '',
    subject: props.subjects[0] || '',
    message: '',
    preferred_contact: 'whatsapp',
    consent: false,
    website: '', // honeypot
});

const loading = ref(false);
const success = ref(null);
const errors = ref({});
const generalError = ref(null);

async function submit() {
    if (loading.value) return;
    loading.value = true;
    errors.value = {};
    generalError.value = null;

    try {
        const { data } = await window.axios.post(props.endpoint, { ...form });
        success.value = data.message;
    } catch (e) {
        if (e.response?.status === 422) {
            errors.value = e.response.data.errors || {};
        } else if (e.response?.status === 429) {
            generalError.value = 'Has enviado varios mensajes seguidos. Espera un minuto e inténtalo de nuevo.';
        } else {
            generalError.value = 'No se pudo enviar el mensaje. Inténtalo de nuevo o escríbeme por WhatsApp.';
        }
    } finally {
        loading.value = false;
    }
}

function err(field) {
    return errors.value[field]?.[0];
}
</script>

<template>
    <div class="rounded-3xl border border-cream-200 bg-white p-6 sm:p-9">
        <div v-if="success" class="rounded-2xl border border-sage-200 bg-sage-50 p-6 text-center">
            <div class="mx-auto mb-3 grid size-12 place-items-center rounded-full bg-sage-600 text-cream-50">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                    <path d="M20 6 9 17l-5-5" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </div>
            <p class="font-serif text-lg text-sage-800">¡Mensaje enviado!</p>
            <p class="mt-1 text-sm text-ink-soft">{{ success }}</p>
        </div>

        <form v-else class="space-y-5" @submit.prevent="submit">
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-sage-700">Nombre *</label>
                    <input v-model="form.name" type="text" required
                        class="w-full rounded-xl border border-cream-200 bg-cream-50 px-4 py-3 text-sm outline-none focus:border-sage-400" />
                    <p v-if="err('name')" class="mt-1 text-xs font-semibold text-clay-500">{{ err('name') }}</p>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-sage-700">Email *</label>
                    <input v-model="form.email" type="email" required
                        class="w-full rounded-xl border border-cream-200 bg-cream-50 px-4 py-3 text-sm outline-none focus:border-sage-400" />
                    <p v-if="err('email')" class="mt-1 text-xs font-semibold text-clay-500">{{ err('email') }}</p>
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-sage-700">Teléfono / WhatsApp</label>
                    <input v-model="form.phone" type="tel"
                        class="w-full rounded-xl border border-cream-200 bg-cream-50 px-4 py-3 text-sm outline-none focus:border-sage-400" />
                    <p v-if="err('phone')" class="mt-1 text-xs font-semibold text-clay-500">{{ err('phone') }}</p>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-sage-700">Asunto</label>
                    <select v-model="form.subject"
                        class="w-full rounded-xl border border-cream-200 bg-cream-50 px-4 py-3 text-sm outline-none focus:border-sage-400">
                        <option v-for="s in props.subjects" :key="s" :value="s">{{ s }}</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-semibold text-sage-700">Cuéntame brevemente qué te trae *</label>
                <textarea v-model="form.message" rows="5" required
                    class="w-full rounded-xl border border-cream-200 bg-cream-50 px-4 py-3 text-sm outline-none focus:border-sage-400"></textarea>
                <p v-if="err('message')" class="mt-1 text-xs font-semibold text-clay-500">{{ err('message') }}</p>
            </div>

            <div>
                <span class="mb-2 block text-sm font-semibold text-sage-700">¿Cómo prefieres que te responda?</span>
                <div class="flex flex-wrap gap-2">
                    <label v-for="opt in [['whatsapp','WhatsApp'],['email','Email'],['llamada','Llamada']]" :key="opt[0]"
                        class="cursor-pointer rounded-full border px-4 py-2 text-sm font-semibold transition-colors"
                        :class="form.preferred_contact === opt[0]
                            ? 'border-sage-600 bg-sage-600 text-cream-50'
                            : 'border-cream-200 bg-cream-50 text-ink-soft hover:border-sage-300'">
                        <input type="radio" class="sr-only" name="pref" :value="opt[0]" v-model="form.preferred_contact" />
                        {{ opt[1] }}
                    </label>
                </div>
            </div>

            <input v-model="form.website" type="text" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true" />

            <label class="flex items-start gap-3 text-sm text-ink-soft">
                <input v-model="form.consent" type="checkbox" class="mt-1 size-4 rounded border-cream-200 accent-sage-600" />
                <span>He leído y acepto la <a href="/privacidad" class="font-semibold text-sage-700 underline">política de privacidad</a>.</span>
            </label>
            <p v-if="err('consent')" class="text-xs font-semibold text-clay-500">{{ err('consent') }}</p>

            <p v-if="generalError" class="text-sm font-semibold text-clay-500">{{ generalError }}</p>

            <button type="submit" class="btn btn-primary w-full sm:w-auto" :disabled="loading" :class="{ 'opacity-50': loading }">
                {{ loading ? 'Enviando…' : 'Enviar mensaje' }}
            </button>
        </form>
    </div>
</template>
