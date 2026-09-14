<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PromotionController extends Controller
{
    public function index(): View
    {
        return view('admin.promotions.index', [
            'promotions' => Promotion::ordered()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $nextPosition = (int) Promotion::max('position') + 1;

        Promotion::create([
            ...$data,
            'position' => $nextPosition,
            'is_active' => true,
        ]);

        return back()->with('status', 'Promoción añadida.');
    }

    public function update(Request $request, Promotion $promotion): RedirectResponse
    {
        $data = $this->validated($request);

        $promotion->update($data);

        return back()->with('status', 'Promoción actualizada.');
    }

    public function toggle(Promotion $promotion): RedirectResponse
    {
        $promotion->update(['is_active' => ! $promotion->is_active]);

        return back()->with('status', $promotion->is_active ? 'Promoción activada.' : 'Promoción oculta de la página de inicio.');
    }

    /**
     * Guarda el nuevo orden tras usar los botones subir/bajar (se envía el
     * array completo de ids en el orden final, mismo patrón que
     * CustomSection/ChatbotFaq).
     */
    public function reorder(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:promotions,id'],
        ]);

        foreach ($data['ids'] as $position => $id) {
            Promotion::where('id', $id)->update(['position' => $position]);
        }

        return back()->with('status', 'Orden actualizado.');
    }

    public function destroy(Promotion $promotion): RedirectResponse
    {
        $promotion->delete();

        return back()->with('status', 'Promoción eliminada.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999.99'],
            'description' => ['required', 'string', 'max:2000'],
            'valid_until' => ['nullable', 'date'],
        ]);
    }
}
