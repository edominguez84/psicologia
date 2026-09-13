<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CustomSectionController extends Controller
{
    private const IMAGE_RULES = ['nullable', 'image', 'max:2048', 'dimensions:max_width=2000,max_height=2000'];

    private const IMAGE_MESSAGES = [
        'image.image' => 'Debe ser una imagen (PNG, JPG o similar).',
        'image.max' => 'La imagen no debe superar 2 MB.',
        'image.dimensions' => 'La imagen no debe superar 2000×2000 píxeles.',
    ];

    public function index(): View
    {
        return view('admin.custom-sections.index', [
            'sections' => CustomSection::ordered()->get(),
        ]);
    }

    public function edit(CustomSection $customSection): View
    {
        return view('admin.custom-sections.edit', [
            'section' => $customSection,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string', 'max:4000'],
            'image' => self::IMAGE_RULES,
        ], self::IMAGE_MESSAGES);

        $imagePath = $request->hasFile('image')
            ? $request->file('image')->store('custom-sections', 'public')
            : null;

        $nextPosition = (int) CustomSection::max('position') + 1;

        CustomSection::create([
            'title' => $data['title'],
            'body' => $data['body'],
            'image_path' => $imagePath,
            'position' => $nextPosition,
            'is_active' => true,
        ]);

        return back()->with('status', 'Sección añadida a la página de inicio.');
    }

    public function update(Request $request, CustomSection $customSection): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string', 'max:4000'],
            'image' => self::IMAGE_RULES,
        ], self::IMAGE_MESSAGES);

        if ($request->hasFile('image')) {
            if ($customSection->image_path) {
                Storage::disk('public')->delete($customSection->image_path);
            }
            $data['image_path'] = $request->file('image')->store('custom-sections', 'public');
        }

        $customSection->update([
            'title' => $data['title'],
            'body' => $data['body'],
            ...(isset($data['image_path']) ? ['image_path' => $data['image_path']] : []),
        ]);

        return back()->with('status', 'Sección actualizada.');
    }

    public function toggle(CustomSection $customSection): RedirectResponse
    {
        $customSection->update(['is_active' => ! $customSection->is_active]);

        return back()->with('status', $customSection->is_active ? 'Sección activada.' : 'Sección oculta de la página de inicio.');
    }

    /**
     * Guarda el nuevo orden tras usar los botones subir/bajar (se envía el
     * array completo de ids en el orden final, mismo patrón que ChatbotFaq).
     */
    public function reorder(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:custom_sections,id'],
        ]);

        foreach ($data['ids'] as $position => $id) {
            CustomSection::where('id', $id)->update(['position' => $position]);
        }

        return back()->with('status', 'Orden actualizado.');
    }

    public function destroy(CustomSection $customSection): RedirectResponse
    {
        if ($customSection->image_path) {
            Storage::disk('public')->delete($customSection->image_path);
        }

        $customSection->delete();

        return back()->with('status', 'Sección eliminada.');
    }
}
