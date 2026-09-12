<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatbotFaq;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChatbotFaqController extends Controller
{
    public function index(): View
    {
        return view('admin.chatbot-faqs.index', [
            'faqs' => ChatbotFaq::ordered()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:160'],
            'answer'   => ['required', 'string', 'max:1000'],
        ]);

        $nextPosition = (int) ChatbotFaq::max('position') + 1;

        ChatbotFaq::create([
            ...$data,
            'position'  => $nextPosition,
            'is_active' => true,
        ]);

        return back()->with('status', 'Pregunta añadida al chatbot.');
    }

    public function update(Request $request, ChatbotFaq $chatbotFaq): RedirectResponse
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:160'],
            'answer'   => ['required', 'string', 'max:1000'],
        ]);

        $chatbotFaq->update($data);

        return back()->with('status', 'Pregunta actualizada.');
    }

    public function toggle(ChatbotFaq $chatbotFaq): RedirectResponse
    {
        $chatbotFaq->update(['is_active' => ! $chatbotFaq->is_active]);

        return back()->with('status', $chatbotFaq->is_active ? 'Pregunta activada.' : 'Pregunta oculta del chat.');
    }

    /**
     * Guarda el nuevo orden tras usar los botones subir/bajar (se envía el
     * array completo de ids en el orden final, mismo patrón que Gallery).
     */
    public function reorder(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids'   => ['required', 'array'],
            'ids.*' => ['integer', 'exists:chatbot_faqs,id'],
        ]);

        foreach ($data['ids'] as $position => $id) {
            ChatbotFaq::where('id', $id)->update(['position' => $position]);
        }

        return back()->with('status', 'Orden actualizado.');
    }

    public function destroy(ChatbotFaq $chatbotFaq): RedirectResponse
    {
        $chatbotFaq->delete();

        return back()->with('status', 'Pregunta eliminada.');
    }
}
