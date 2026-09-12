<x-admin-layout title="Preguntas del chatbot">
    <h1 class="text-2xl font-serif text-sky-800">Preguntas del chatbot (Rebecca)</h1>
    <p class="mt-2 text-sm text-ink-soft">
        Estas son las preguntas que el visitante puede elegir como botones dentro del chat, con la
        respuesta fija que verá al seleccionarlas. Usa las flechas para reordenarlas, oculta las
        que no quieras mostrar sin borrarlas, y edita el texto cuando quieras. Solo la super
        administradora puede gestionar esta sección.
    </p>

    <div
        x-data="{
            faqs: {{ json_encode($faqs->map(fn ($f) => ['id' => $f->id, 'question' => $f->question, 'answer' => $f->answer, 'is_active' => $f->is_active])) }},
            editing: null,
            moveUp(i) { if (i === 0) return; [this.faqs[i-1], this.faqs[i]] = [this.faqs[i], this.faqs[i-1]]; this.saveOrder(); },
            moveDown(i) { if (i === this.faqs.length - 1) return; [this.faqs[i+1], this.faqs[i]] = [this.faqs[i], this.faqs[i+1]]; this.saveOrder(); },
            saveOrder() {
                const ids = this.faqs.map(f => f.id);
                fetch('{{ route('admin.chatbot-faqs.reorder') }}', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                    body: JSON.stringify({ ids }),
                });
            },
        }"
        class="mt-8 space-y-4"
    >
        <template x-for="(faq, i) in faqs" :key="faq.id">
            <div class="rounded-2xl border border-paper-200 bg-white p-5" :class="{ 'opacity-50': !faq.is_active }">
                <div class="flex items-start gap-4">
                    <div class="flex shrink-0 flex-col gap-1.5">
                        <button type="button" @click="moveUp(i)" class="grid size-8 place-items-center rounded-full border border-paper-200 text-sky-700 hover:bg-sky-50" aria-label="Subir">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none"><path d="M12 19V5M5 12l7-7 7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                        <button type="button" @click="moveDown(i)" class="grid size-8 place-items-center rounded-full border border-paper-200 text-sky-700 hover:bg-sky-50" aria-label="Bajar">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12l7 7 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                    </div>

                    <div class="min-w-0 flex-1">
                        <template x-if="editing !== faq.id">
                            <div>
                                <p class="font-semibold text-ink" x-text="faq.question"></p>
                                <p class="mt-1 text-sm leading-relaxed text-ink-soft" x-text="faq.answer"></p>
                            </div>
                        </template>

                        <form
                            x-show="editing === faq.id"
                            method="POST"
                            :action="`/admin/chatbot-faqs/${faq.id}`"
                            class="space-y-3"
                        >
                            @csrf
                            @method('PUT')
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-sky-700">Pregunta (texto del botón)</label>
                                <input type="text" name="question" x-model="faq.question" maxlength="160" required
                                    class="w-full rounded-lg border border-paper-200 bg-paper-50 px-3 py-2 text-sm outline-none focus:border-sky-400">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-sky-700">Respuesta</label>
                                <textarea name="answer" x-model="faq.answer" rows="3" maxlength="1000" required
                                    class="w-full rounded-lg border border-paper-200 bg-paper-50 px-3 py-2 text-sm outline-none focus:border-sky-400"></textarea>
                            </div>
                            <div class="flex gap-2">
                                <button type="submit" class="btn btn-primary !px-4 !py-2 text-sm">Guardar</button>
                                <button type="button" @click="editing = null" class="text-sm font-semibold text-ink-soft hover:underline">Cancelar</button>
                            </div>
                        </form>
                    </div>

                    <div class="flex shrink-0 flex-col items-end gap-2 text-xs">
                        <button type="button" @click="editing = editing === faq.id ? null : faq.id" class="font-semibold text-sky-700 hover:underline" x-show="editing !== faq.id">
                            Editar
                        </button>

                        <form method="POST" :action="`/admin/chatbot-faqs/${faq.id}/toggle`">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="font-semibold hover:underline" :class="faq.is_active ? 'text-clay-500' : 'text-sky-700'">
                                <span x-text="faq.is_active ? 'Ocultar' : 'Activar'"></span>
                            </button>
                        </form>

                        <form method="POST" :action="`/admin/chatbot-faqs/${faq.id}`" onsubmit="return confirm('¿Eliminar esta pregunta definitivamente?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="font-semibold text-clay-500 hover:underline">Eliminar</button>
                        </form>
                    </div>
                </div>
            </div>
        </template>

        <template x-if="faqs.length === 0">
            <p class="rounded-2xl border border-dashed border-paper-200 p-6 text-center text-sm text-ink-soft">
                Todavía no hay preguntas configuradas.
            </p>
        </template>
    </div>

    <h2 class="mb-3 mt-10 font-serif text-lg text-sky-800">Añadir una pregunta</h2>
    <form method="POST" action="{{ route('admin.chatbot-faqs.store') }}" class="max-w-xl space-y-4">
        @csrf
        <div>
            <label for="question" class="mb-1.5 block text-sm font-semibold text-sky-700">Pregunta (texto del botón)</label>
            <input type="text" name="question" id="question" maxlength="160" required
                class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400"
                placeholder="¿Cuánto cuesta una sesión?">
            @error('question')<p class="mt-1 text-xs font-semibold text-clay-500">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="answer" class="mb-1.5 block text-sm font-semibold text-sky-700">Respuesta</label>
            <textarea name="answer" id="answer" rows="4" maxlength="1000" required
                class="w-full rounded-xl border border-paper-200 bg-paper-50 px-4 py-2.5 text-sm outline-none focus:border-sky-400"
                placeholder="El costo por sesión es..."></textarea>
            @error('answer')<p class="mt-1 text-xs font-semibold text-clay-500">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="btn btn-primary">Añadir pregunta</button>
    </form>
</x-admin-layout>
