<script setup>
import { nextTick, reactive, ref, watch } from 'vue';

const props = defineProps({
    botName: { type: String, default: 'Rebecca' },
    botTagline: { type: String, default: 'Asistente virtual' },
    avatar: { type: String, default: null },
    endpoint: { type: String, default: null },
    // Chat en vivo con IA (ver Api\ChatbotMessageController) — mismo motor
    // que el bot de Telegram. Si aiEnabled es false, el widget usa el flujo
    // de siempre (recoger datos por formulario + FAQ por botones), sin
    // llamar a este endpoint.
    chatEndpoint: { type: String, default: null },
    aiEnabled: { type: Boolean, default: false },
    // Saludo configurado en /admin/chatbot-channels (App\Services\
    // ChatbotAiService::presentation()) — solo aplica con aiEnabled, para
    // que el primer mensaje que ve el paciente coincida con el que la IA
    // usaría si se lo preguntaran. Null si no se configuró uno.
    greeting: { type: String, default: null },
    // Modo demo (sin backend, p.ej. Netlify): no guarda el lead, solo simula.
    demoMode: { type: Boolean, default: false },
    whatsapp: { type: String, default: '' },
    // Preguntas frecuentes administrables desde /admin/chatbot-faqs
    // (super_admin), en el orden que ella definió. Cada una: {id, question, answer}.
    faqs: { type: Array, default: () => [] },
    // Cierre automático por inactividad (configurable en
    // /admin/chatbot-channels) — aplica tanto al modo FAQ como al modo IA.
    // Se resuelve enteramente en el navegador: no depende de ningún cron ni
    // llamada al backend, a diferencia del mismo timeout para Telegram (ver
    // App\Console\Commands\CloseInactiveChatbotConversations), que sí
    // necesita un push real porque no hay una ventana de chat abierta
    // esperando la respuesta.
    inactivityTimeoutMinutes: { type: Number, default: 5 },
    farewellMessage: { type: String, default: 'Veo que no tienes otra consulta, buen día, adiós.' },
});

// Pasos del flujo sin IA: recoger datos de contacto antes de dejar conversar.
// 'name' -> 'email' -> 'phone' -> 'chat' (respuestas predefinidas por botones rápidos).
// Con IA activa ('chat-ai'), se conversa libremente desde el primer mensaje —
// es la propia IA quien pide nombre/correo/teléfono en la conversación.
const step = ref('intro');
const open = ref(false);
const sending = ref(false);
const errorMsg = ref(null);

const lead = reactive({ name: '', email: '', phone: '' });
const draft = ref('');
const messages = ref([]);
const scrollEl = ref(null);

function pushMessage(from, text) {
    messages.value.push({ from, text });
    nextTick(() => {
        if (scrollEl.value) scrollEl.value.scrollTop = scrollEl.value.scrollHeight;
    });
}

// Cierre por inactividad: el reloj se reinicia solo cuando el visitante
// manda un mensaje nuevo (no por abrir la ventana ni por leer la
// respuesta) — ver resetInactivityTimer(), llamado desde submitPhone(),
// sendDraft() y sendAiMessage().
let inactivityTimer = null;

function clearInactivityTimer() {
    if (inactivityTimer) {
        clearTimeout(inactivityTimer);
        inactivityTimer = null;
    }
}

function resetInactivityTimer() {
    clearInactivityTimer();
    if (!(props.inactivityTimeoutMinutes > 0)) return;

    inactivityTimer = setTimeout(() => {
        endConversationByInactivity();
    }, props.inactivityTimeoutMinutes * 60 * 1000);
}

function endConversationByInactivity() {
    if (!open.value || step.value === 'intro') return;

    pushMessage('bot', props.farewellMessage);
    // Breve pausa para que el visitante alcance a leer la despedida antes
    // de que la ventana se cierre sola.
    setTimeout(() => {
        close();
    }, 2500);
}

function toggle() {
    open.value = !open.value;
    if (open.value && step.value === 'intro') {
        if (props.aiEnabled && !props.demoMode) {
            step.value = 'chat-ai';
            pushMessage('bot', props.greeting || `¡Hola! 🌿 Soy ${props.botName}, asistente virtual. ¿En qué te puedo ayudar hoy?`);
        } else {
            step.value = 'name';
            pushMessage('bot', `¡Hola! 🌿 Soy ${props.botName}, asistente virtual. ¿Con quién tengo el gusto?`);
        }
    }
}

function close() {
    open.value = false;
    clearInactivityTimer();
}

function submitName() {
    if (!lead.name.trim()) return;
    pushMessage('user', lead.name);
    step.value = 'email';
    pushMessage('bot', `Encantada, ${lead.name.split(' ')[0]}. ¿A qué correo te puedo escribir?`);
}

function submitEmail() {
    if (!lead.email.trim() || !lead.email.includes('@')) {
        errorMsg.value = 'Escribe un correo válido.';
        return;
    }
    errorMsg.value = null;
    pushMessage('user', lead.email);
    step.value = 'phone';
    pushMessage('bot', '¿Y un teléfono de contacto? (puedes dejarlo en blanco si prefieres)');
}

async function submitPhone() {
    if (lead.phone.trim()) {
        pushMessage('user', lead.phone);
    } else {
        pushMessage('user', 'Prefiero no darlo');
    }
    step.value = 'chat';
    pushMessage('bot', `Gracias, ${lead.name.split(' ')[0]}. Elige una opción o escríbeme directamente lo que necesitas:`);
    resetInactivityTimer();
    await saveLead();
}

async function saveLead() {
    if (props.demoMode) return;

    sending.value = true;
    try {
        await window.axios.post(props.endpoint, {
            name: lead.name,
            email: lead.email,
            phone: lead.phone || null,
            transcript: messages.value,
        });
    } catch (e) {
        // No bloquea la conversación si falla el guardado.
        console.error('No se pudo guardar el contacto del chat.', e);
    } finally {
        sending.value = false;
    }
}

function useFaq(faq) {
    pushMessage('user', faq.question);
    pushMessage('bot', faq.answer);
}

function sendDraft() {
    const text = draft.value.trim();
    if (!text) return;
    pushMessage('user', text);
    draft.value = '';
    pushMessage('bot', `Gracias por contarme. Le paso este mensaje a la psicóloga y te contactaremos pronto a ${lead.email || 'tu correo'}. Si prefieres una respuesta inmediata, escríbenos por WhatsApp.`);
    resetInactivityTimer();
}

async function sendAiMessage() {
    const text = draft.value.trim();
    if (!text || sending.value) return;
    pushMessage('user', text);
    draft.value = '';
    resetInactivityTimer();
    sending.value = true;
    try {
        const { data } = await window.axios.post(props.chatEndpoint, { message: text });
        pushMessage('bot', data.reply);
    } catch (e) {
        pushMessage('bot', 'Disculpa, tuve un problema respondiendo. Intenta de nuevo o escríbenos por WhatsApp.');
    } finally {
        sending.value = false;
    }
}

</script>

<template>
    <div class="fixed bottom-5 right-5 z-[90] flex flex-col items-end gap-3 sm:bottom-6 sm:right-6">
        <Transition name="fade">
            <div
                v-if="open"
                class="flex h-[32rem] max-h-[75vh] w-[22rem] max-w-[calc(100vw-2.5rem)] flex-col overflow-hidden rounded-3xl border border-paper-200 bg-card-fixed shadow-2xl"
            >
                <div class="flex items-center justify-between bg-ink-panel px-5 py-4 text-on-dark">
                    <div class="flex items-center gap-3">
                        <div class="grid size-9 shrink-0 place-items-center overflow-hidden rounded-full bg-sky-600 text-sm font-bold">
                            <img v-if="avatar" :src="avatar" :alt="botName" class="size-full object-cover" />
                            <span v-else>{{ botName.charAt(0) }}</span>
                        </div>
                        <div>
                            <p class="text-sm font-bold leading-tight">{{ botName }}</p>
                            <p class="text-xs text-on-dark-soft">{{ botTagline }} · en línea</p>
                        </div>
                    </div>
                    <button type="button" class="grid size-8 place-items-center rounded-full hover:bg-white/10" aria-label="Cerrar" @click="close">
                        <svg width="16" height="16" viewBox="0 0 18 18" fill="none"><path d="M4 4l10 10M14 4L4 14" stroke="currentColor" stroke-width="2" stroke-linecap="round" /></svg>
                    </button>
                </div>

                <div ref="scrollEl" class="flex-1 space-y-3 overflow-y-auto bg-paper-50 px-4 py-4">
                    <div v-for="(m, i) in messages" :key="i" class="flex" :class="m.from === 'user' ? 'justify-end' : 'justify-start'">
                        <div
                            class="max-w-[85%] rounded-2xl px-4 py-2.5 text-sm leading-relaxed"
                            :class="m.from === 'user' ? 'bg-sky-600 text-on-dark' : 'border border-paper-200 bg-card-fixed text-on-card-fixed'"
                        >
                            {{ m.text }}
                        </div>
                    </div>

                    <div v-if="step === 'chat-ai' && sending" class="flex justify-start">
                        <div class="rounded-2xl border border-paper-200 bg-card-fixed px-4 py-2.5 text-sm text-on-card-fixed-soft">
                            Escribiendo…
                        </div>
                    </div>

                    <div v-if="step === 'chat'" class="flex flex-wrap gap-2 pt-1">
                        <button
                            v-for="faq in faqs" :key="faq.id" type="button"
                            class="rounded-full border border-sky-200 bg-card-fixed px-3 py-1.5 text-xs font-semibold text-sky-700 hover:border-sky-400"
                            @click="useFaq(faq)"
                        >
                            {{ faq.question }}
                        </button>
                        <a
                            :href="whatsapp" target="_blank" rel="noopener"
                            class="rounded-full bg-sky-600 px-3 py-1.5 text-xs font-semibold text-on-dark hover:bg-sky-700"
                        >
                            Hablar por WhatsApp
                        </a>
                    </div>
                </div>

                <div class="border-t border-paper-200 bg-card-fixed p-3">
                    <form v-if="step === 'name'" class="flex gap-2" @submit.prevent="submitName">
                        <input v-model="lead.name" type="text" placeholder="Tu nombre" required autofocus
                            class="w-full rounded-full border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400" />
                        <button type="submit" class="grid size-10 shrink-0 place-items-center rounded-full bg-sky-600 text-on-dark hover:bg-sky-700">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M3 20l18-8L3 4v6l12 2-12 2z"/></svg>
                        </button>
                    </form>

                    <form v-else-if="step === 'email'" class="flex gap-2" @submit.prevent="submitEmail">
                        <input v-model="lead.email" type="email" placeholder="tu@correo.com" required autofocus
                            class="w-full rounded-full border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400" />
                        <button type="submit" class="grid size-10 shrink-0 place-items-center rounded-full bg-sky-600 text-on-dark hover:bg-sky-700">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M3 20l18-8L3 4v6l12 2-12 2z"/></svg>
                        </button>
                    </form>
                    <p v-if="step === 'email' && errorMsg" class="mt-1 pl-2 text-xs font-semibold text-clay-500">{{ errorMsg }}</p>

                    <form v-else-if="step === 'phone'" class="flex gap-2" @submit.prevent="submitPhone">
                        <input v-model="lead.phone" type="tel" placeholder="Teléfono (opcional)" autofocus
                            class="w-full rounded-full border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400" />
                        <button type="submit" class="grid size-10 shrink-0 place-items-center rounded-full bg-sky-600 text-on-dark hover:bg-sky-700">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M3 20l18-8L3 4v6l12 2-12 2z"/></svg>
                        </button>
                    </form>

                    <form v-else-if="step === 'chat'" class="flex gap-2" @submit.prevent="sendDraft">
                        <input v-model="draft" type="text" placeholder="Escribe tu mensaje…"
                            class="w-full rounded-full border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400" />
                        <button type="submit" class="grid size-10 shrink-0 place-items-center rounded-full bg-sky-600 text-on-dark hover:bg-sky-700">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M3 20l18-8L3 4v6l12 2-12 2z"/></svg>
                        </button>
                    </form>

                    <form v-else-if="step === 'chat-ai'" class="flex gap-2" @submit.prevent="sendAiMessage">
                        <input v-model="draft" type="text" placeholder="Escribe tu mensaje…" :disabled="sending"
                            class="w-full rounded-full border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400 disabled:opacity-60" />
                        <button type="submit" :disabled="sending" class="grid size-10 shrink-0 place-items-center rounded-full bg-sky-600 text-on-dark hover:bg-sky-700 disabled:opacity-60">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M3 20l18-8L3 4v6l12 2-12 2z"/></svg>
                        </button>
                    </form>
                    <p class="mt-2 px-2 text-[11px] leading-snug text-on-card-fixed-soft">
                        Asistente informativo, no sustituye atención profesional.
                        <a :href="whatsapp" target="_blank" rel="noopener" class="font-semibold text-sky-700 underline">Hablar con la psicóloga por WhatsApp</a>
                    </p>
                </div>
            </div>
        </Transition>

        <div class="flex items-center gap-3">
            <a
                v-if="!open"
                :href="whatsapp" target="_blank" rel="noopener"
                aria-label="Escribir por WhatsApp"
                class="grid size-12 shrink-0 place-items-center rounded-full bg-emerald-700 text-white shadow-xl transition-transform hover:scale-105"
            >
                <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 00-8.5 15.3L2 22l4.8-1.5A10 10 0 1012 2zm0 18a8 8 0 01-4.1-1.1l-.3-.2-2.8.9.9-2.7-.2-.3A8 8 0 1112 20zm4.4-5.6c-.2-.1-1.4-.7-1.6-.8s-.4-.1-.6.1-.7.8-.8 1-.3.2-.5.1a6.5 6.5 0 01-1.9-1.2 7.2 7.2 0 01-1.3-1.7c-.1-.2 0-.4.1-.5l.4-.5.3-.5v-.5c0-.1-.6-1.5-.8-2s-.4-.4-.6-.4h-.5a1 1 0 00-.7.3A2.9 2.9 0 006 6.6c0 1.7 1.3 3.4 1.5 3.6s2.5 3.9 6.1 5.3c2.2.8 2.7.7 3.2.6s1.4-.6 1.6-1.1a2 2 0 00.1-1.1c0-.2-.2-.3-.4-.4z"/></svg>
            </a>

            <button
                type="button"
                :aria-label="open ? 'Cerrar chat' : 'Abrir chat'"
                class="relative flex shrink-0 items-center gap-2.5 rounded-full bg-clay-400 py-1.5 pl-1.5 shadow-xl transition-transform hover:scale-105"
                :class="open ? 'size-12 justify-center pr-1.5' : 'pr-4'"
                @click="toggle"
            >
                <template v-if="!open">
                    <span class="relative grid size-9 shrink-0 place-items-center overflow-hidden rounded-full bg-white/20">
                        <img v-if="avatar" :src="avatar" :alt="botName" class="size-full object-cover" />
                        <span v-else class="text-sm font-bold text-white">{{ botName.charAt(0) }}</span>
                        <span class="absolute -right-0.5 -top-0.5 size-2.5 rounded-full border-2 border-white bg-green-500"></span>
                    </span>
                    <span class="text-sm font-bold text-white">Asistente 24/7</span>
                </template>
                <svg v-else width="20" height="20" viewBox="0 0 18 18" fill="none"><path d="M4 4l10 10M14 4L4 14" stroke="currentColor" stroke-width="2" stroke-linecap="round" /></svg>
            </button>
        </div>
    </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.15s ease, transform 0.15s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: translateY(8px); }
</style>
