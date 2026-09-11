<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SiteSettingsService;
use App\Support\SiteContentSections;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ContentController extends Controller
{
    public function __construct(private SiteSettingsService $settings)
    {
    }

    public function edit(string $section)
    {
        $schema = SiteContentSections::find($section);
        abort_if(! $schema, Response::HTTP_NOT_FOUND);

        // Overrides guardados + valores por defecto de config/site.php, para
        // que el formulario siempre muestre el valor vigente en el sitio.
        $overrides = $this->settings->get('content')[$section] ?? [];
        $values = array_replace_recursive(config("site.$section", []), $overrides);

        return view('admin.content.edit', [
            'sectionKey' => $section,
            'schema' => $schema,
            'values' => $values,
        ]);
    }

    public function update(Request $request, string $section)
    {
        $schema = SiteContentSections::find($section);
        abort_if(! $schema, Response::HTTP_NOT_FOUND);

        $value = $this->extractSectionValue($request, $schema['fields']);

        $content = $this->settings->get('content');
        $content[$section] = $value;
        $this->settings->set('content', $content);

        return redirect()
            ->route('admin.content.edit', $section)
            ->with('status', 'Contenido actualizado.');
    }

    /**
     * Reconstruye el array de la sección a partir del formulario, respetando
     * el esquema declarado en SiteContentSections (list -> array de líneas,
     * repeater -> array de objetos, text/textarea -> string).
     */
    private function extractSectionValue(Request $request, array $fields): array
    {
        $result = [];

        foreach ($fields as $key => $field) {
            $result[$key] = match ($field['type']) {
                'list' => array_values(array_filter(array_map(
                    'trim',
                    explode("\n", (string) $request->input($key, ''))
                ), fn ($line) => $line !== '')),
                'repeater' => array_values(array_map(
                    fn (array $row) => array_intersect_key($row, $field['fields']),
                    array_filter((array) $request->input($key, []), 'is_array')
                )),
                default => (string) $request->input($key, ''),
            };
        }

        return $result;
    }
}
