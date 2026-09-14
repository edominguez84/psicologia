<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SiteSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfanityFilterController extends Controller
{
    public function __construct(private SiteSettingsService $settings)
    {
    }

    public function edit(): View
    {
        $config = $this->settings->get('profanity_filter', ['extra_words' => [], 'ban_threshold' => 5]);

        return view('admin.profanity-filter.edit', [
            'extraWords' => $config['extra_words'] ?? [],
            'banThreshold' => $config['ban_threshold'] ?? 5,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'extra_words' => ['nullable', 'string', 'max:2000'],
            'ban_threshold' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        // Una palabra por línea en el textarea, se limpia a un array simple.
        $words = collect(explode("\n", (string) ($data['extra_words'] ?? '')))
            ->map(fn ($w) => trim($w))
            ->filter()
            ->values()
            ->all();

        $this->settings->set('profanity_filter', [
            'extra_words' => $words,
            'ban_threshold' => (int) $data['ban_threshold'],
        ]);

        return back()->with('status', 'Filtro de contenido actualizado.');
    }
}
