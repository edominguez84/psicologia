<script setup>
import { reactive, ref } from 'vue';

const props = defineProps({
    subjects: { type: Array, default: () => [] },
    endpoint: { type: String, default: null },
    // Modo demo (sin backend, p.ej. despliegue estático en Netlify):
    // en vez de hacer POST, arma el mensaje y abre WhatsApp.
    demoMode: { type: Boolean, default: false },
    whatsapp: { type: String, default: '' },
    // Permite preseleccionar el asunto (p.ej. desde el modal de "agendar llamada").
    initialSubject: { type: String, default: null },
    // Qué campos fijos opcionales mostrar (phone/subject/preferred_contact),
    // configurado desde /admin/contact-form. Ausencia de key = visible.
    visibleFields: { type: Object, default: () => ({}) },
    // Campos personalizados definidos por el admin: [{key, label, required}].
    customFields: { type: Array, default: () => [] },
});

const emit = defineEmits(['sent']);

function isVisible(field) {
    return props.visibleFields[field] ?? true;
}

const form = reactive({
    name: '',
    email: '',
    phone: '',
    subject: props.initialSubject || props.subjects[0] || '',
    message: '',
    preferred_contact: 'whatsapp',
    consent: false,
    website: '', // honeypot
    custom: Object.fromEntries(props.customFields.map((f) => [f.key, ''])),
});

const loading = ref(false);
const success = ref(null);
const errors = ref({});
const generalError = ref(null);

async function submit() {
    if (loading.value) return;

    const missingCustom = props.customFields.find((f) => f.required && !form.custom[f.key]);

    if (!form.name || !form.email || form.message.length < 10 || !form.consent || missingCustom) {
        errors.value = {
            ...(!form.name ? { name: ['El campo nombre es obligatorio.'] } : {}),
            ...(!form.email ? { email: ['El campo email es obligatorio.'] } : {}),
            ...(form.message.length < 10 ? { message: ['Cuéntame un poco más para poder ayudarte (mínimo 10 caracteres).'] } : {}),
            ...(!form.consent ? { consent: ['Debes aceptar la política de privacidad para continuar.'] } : {}),
            ...(missingCustom ? { [`custom_${missingCustom.key}`]: ['Este campo es obligatorio.'] } : {}),
        };
        return;
    }

    if (props.demoMode) {
        const text = [
            `Hola, soy ${form.name}.`,
            form.subject ? `Asunto: ${form.subject}.` : null,
            form.message,
        ].filter(Boolean).join(' ');
        window.open(`${props.whatsapp}${props.whatsapp.includes('?') ? '&' : '?'}text=${encodeURIComponent(text)}`, '_blank', 'noopener');
        success.value = 'Se ha abierto WhatsApp con tu mensaje listo para enviar. (Sitio de demostración: este formulario no guarda datos ni envía email; la versión con Laravel + MySQL sí lo hace.)';
        emit('sent');
        return;
    }

    loading.value = true;
    errors.value = {};
    generalError.value = null;

    // custom_fields se envía como array de {label, value} para que el
    // backend los guarde legibles sin tener que conocer las keys internas.
    const customFieldsPayload = props.customFields.map((f) => ({
        label: f.label,
        value: form.custom[f.key] || '',
    }));

    try {
        const { data } = await window.axios.post(props.endpoint, {
            ...form,
            custom_fields: customFieldsPayload,
        });
        success.value = data.message;
        emit('sent');
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
    <div class="rounded-3xl border border-paper-200 bg-card-fixed p-6 sm:p-9">
        <div v-if="success" class="rounded-2xl border border-sky-200 bg-sky-50 p-6 text-center">
            <div class="mx-auto mb-3 grid size-12 place-items-center rounded-full bg-sky-600 text-on-dark">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                    <path d="M20 6 9 17l-5-5" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </div>
            <p class="font-serif text-lg text-on-card-fixed">¡Mensaje enviado!</p>
            <p class="mt-1 text-sm text-on-card-fixed-soft">{{ success }}</p>
        </div>

        <form v-else class="space-y-5" @submit.prevent="submit">
            <p v-if="demoMode" class="rounded-xl border border-clay-400/30 bg-paper-100 px-4 py-3 text-xs leading-relaxed text-ink-soft">
                <strong class="text-clay-500">Sitio de demostración:</strong> este formulario abre WhatsApp con tu mensaje. No guarda datos (la versión con Laravel + MySQL sí lo hace).
            </p>
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-sky-700">Nombre *</label>
                    <input v-model="form.name" type="text" required
                        class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-3 text-sm outline-none focus:border-sky-400" />
                    <p v-if="err('name')" class="mt-1 text-xs font-semibold text-clay-500">{{ err('name') }}</p>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-sky-700">Email *</label>
                    <input v-model="form.email" type="email" required
                        class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-3 text-sm outline-none focus:border-sky-400" />
                    <p v-if="err('email')" class="mt-1 text-xs font-semibold text-clay-500">{{ err('email') }}</p>
                </div>
            </div>

            <div v-if="isVisible('phone') || isVisible('subject')" class="grid gap-5 sm:grid-cols-2">
                <div v-if="isVisible('phone')">
                    <label class="mb-1.5 block text-sm font-semibold text-sky-700">Teléfono / WhatsApp</label>
                    <input v-model="form.phone" type="tel"
                        class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-3 text-sm outline-none focus:border-sky-400" />
                    <p v-if="err('phone')" class="mt-1 text-xs font-semibold text-clay-500">{{ err('phone') }}</p>
                </div>
                <div v-if="isVisible('subject')">
                    <label class="mb-1.5 block text-sm font-semibold text-sky-700">Asunto</label>
                    <select v-model="form.subject"
                        class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-3 text-sm outline-none focus:border-sky-400">
                        <option v-for="s in props.subjects" :key="s" :value="s">{{ s }}</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-semibold text-sky-700">Cuéntame brevemente qué te trae *</label>
                <textarea v-model="form.message" rows="5" required
                    class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-3 text-sm outline-none focus:border-sky-400"></textarea>
                <p v-if="err('message')" class="mt-1 text-xs font-semibold text-clay-500">{{ err('message') }}</p>
            </div>

            <div v-for="field in customFields" :key="field.key">
                <label class="mb-1.5 block text-sm font-semibold text-sky-700">
                    {{ field.label }}<span v-if="field.required"> *</span>
                </label>
                <input v-model="form.custom[field.key]" type="text" :required="field.required"
                    class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-3 text-sm outline-none focus:border-sky-400" />
                <p v-if="err(`custom_${field.key}`)" class="mt-1 text-xs font-semibold text-clay-500">{{ err(`custom_${field.key}`) }}</p>
            </div>

            <div v-if="isVisible('preferred_contact')">
                <span class="mb-2 block text-sm font-semibold text-sky-700">¿Cómo prefieres que te responda?</span>
                <div class="flex flex-wrap gap-2">
                    <label v-for="opt in [['whatsapp','WhatsApp'],['email','Email'],['llamada','Llamada']]" :key="opt[0]"
                        class="cursor-pointer rounded-full border px-4 py-2 text-sm font-semibold transition-colors"
                        :class="form.preferred_contact === opt[0]
                            ? 'border-sky-600 bg-sky-600 text-paper-50'
                            : 'border-paper-200 bg-paper-50 text-ink-soft hover:border-sky-300'">
                        <input type="radio" class="sr-only" name="pref" :value="opt[0]" v-model="form.preferred_contact" />
                        {{ opt[1] }}
                    </label>
                </div>
            </div>

            <input v-model="form.website" type="text" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true" />

            <label class="flex items-start gap-3 text-sm text-ink-soft">
                <input v-model="form.consent" type="checkbox" class="mt-1 size-4 rounded border-paper-200 accent-sky-600" />
                <span>He leído y acepto la <a href="/privacidad" class="font-semibold text-sky-700 underline">política de privacidad</a>.</span>
            </label>
            <p v-if="err('consent')" class="text-xs font-semibold text-clay-500">{{ err('consent') }}</p>

            <p v-if="generalError" class="text-sm font-semibold text-clay-500">{{ generalError }}</p>

            <button type="submit" class="btn btn-primary w-full sm:w-auto" :disabled="loading" :class="{ 'opacity-50': loading }">
                {{ loading ? 'Enviando…' : 'Enviar mensaje' }}
            </button>
        </form>
    </div>
</template>
