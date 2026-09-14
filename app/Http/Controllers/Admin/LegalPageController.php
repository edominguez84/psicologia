<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SiteSettingsService;
use App\Support\HtmlSanitizer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class LegalPageController extends Controller
{
    /**
     * Slugs válidos y su título/contenido por defecto, usados cuando la
     * página nunca fue editada desde el panel (para no dejarla en blanco en
     * el primer despliegue). El texto original de privacidad se conserva
     * aquí tal cual vivía antes en privacy.blade.php.
     */
    public const PAGES = ['privacy', 'terms'];

    public function __construct(private SiteSettingsService $settings)
    {
    }

    public function edit(string $page)
    {
        abort_if(! in_array($page, self::PAGES, true), Response::HTTP_NOT_FOUND);

        $saved = $this->settings->get('legal_pages')[$page] ?? null;

        return view('admin.legal-pages.edit', [
            'page' => $page,
            'title' => $saved['title'] ?? $this->defaultTitle($page),
            'bodyHtml' => $saved['body_html'] ?? '',
        ]);
    }

    public function update(Request $request, string $page)
    {
        abort_if(! in_array($page, self::PAGES, true), Response::HTTP_NOT_FOUND);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'body_html' => ['nullable', 'string'],
        ]);

        $legalPages = $this->settings->get('legal_pages');
        $legalPages[$page] = [
            'title' => $data['title'],
            'body_html' => HtmlSanitizer::clean($data['body_html'] ?? ''),
            'updated_at' => now()->toIso8601String(),
        ];
        $this->settings->set('legal_pages', $legalPages);

        return redirect()
            ->route('admin.legal.edit', $page)
            ->with('status', 'Página actualizada.');
    }

    private function defaultTitle(string $page): string
    {
        return match ($page) {
            'privacy' => 'Política de privacidad',
            'terms' => 'Condiciones de uso',
        };
    }
}
