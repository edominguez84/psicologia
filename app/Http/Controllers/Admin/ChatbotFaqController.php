<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatbotFaq;
use App\Services\SiteSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ChatbotFaqController extends Controller
{
    public function __construct(private SiteSettingsService $settings)
    {
    }

    public function index(): View
    {
        return view('admin.chatbot-faqs.index', [
            'faqs' => ChatbotFaq::ordered()->get(),
            'chatbotSettings' => $this->settings->get('chatbot', ['name' => 'Rebecca']),
        ]);
    }

    /**
     * Solo administra la imagen del bot — el nombre y el saludo se
     * configuran en Admin\ChatbotChannelsController (ver
     * ChatbotAiService::presentation()). El campo 'name' aquí guardado ya
     * no se edita desde este formulario, pero se conserva como fallback
     * para el nombre mostrado en modo FAQ (IA apagada), donde no aplica la
     * configuración de la IA.
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'avatar' => ['nullable', 'image', 'max:2048', 'dimensions:max_width=1000,max_height=1000'],
        ], [
            'avatar.image' => 'Debe ser una imagen (PNG, JPG o similar).',
            'avatar.max' => 'La imagen no debe superar 2 MB.',
            'avatar.dimensions' => 'La imagen no debe superar 1000×1000 píxeles.',
        ]);

        $settings = $this->settings->get('chatbot', ['name' => 'Rebecca']);

        if ($request->hasFile('avatar')) {
            if (! empty($settings['avatar_path'])) {
                Storage::disk('public')->delete($settings['avatar_path']);
            }
            $settings['avatar_path'] = $request->file('avatar')->store('chatbot', 'public');
        }

        $this->settings->set('chatbot', $settings);

        return back()->with('status', 'Imagen del chatbot actualizada.');
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
