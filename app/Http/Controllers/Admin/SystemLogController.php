<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SystemLogController extends Controller
{
    private const MAX_LINES = 500;
    private const DEFAULT_LINES = 200;

    /**
     * Visor de storage/logs/laravel.log para diagnosticar fallos, exclusivo
     * de super_admin. Lee desde el final del archivo hacia atrás (sin cargar
     * el archivo completo en memoria, que solo crece con el tiempo) y
     * parsea cada línea con el formato estándar de Monolog para poder
     * colorear por severidad en la vista.
     */
    public function index(Request $request): View
    {
        $requested = (int) $request->integer('lines', self::DEFAULT_LINES);
        $lines = max(10, min(self::MAX_LINES, $requested));

        $path = storage_path('logs/laravel.log');
        $entries = collect();
        $fileExists = is_file($path);
        $fileSize = $fileExists ? filesize($path) : 0;

        if ($fileExists && $fileSize > 0) {
            $entries = $this->tailEntries($path, $lines);
        }

        return view('admin.system-log.index', [
            'entries' => $entries,
            'lines' => $lines,
            'fileExists' => $fileExists,
            'fileSizeKb' => (int) round($fileSize / 1024),
        ]);
    }

    /**
     * Lee las últimas $maxLines "entradas" del log (una entrada = una línea
     * que empieza con "[fecha]", seguida de todas las líneas de stack trace
     * que le pertenecen) usando SplFileObject::seek() para ir directo al
     * final sin recorrer todo el archivo primero.
     */
    private function tailEntries(string $path, int $maxLines): \Illuminate\Support\Collection
    {
        $file = new \SplFileObject($path, 'r');
        $file->seek(PHP_INT_MAX);
        $totalLines = $file->key();

        // Se leen bastantes más líneas crudas que "entradas" pedidas, porque
        // una excepción con stack trace (o, en este proyecto, el volcado
        // completo de un email cuando MAIL_MAILER=log) puede ocupar cientos
        // de líneas de archivo por una sola entrada visible — un margen
        // amplio evita cortar a mitad de una traza sin tener que leer el
        // archivo completo.
        $rawWindow = max($maxLines * 100, 5000);
        $startLine = max(0, $totalLines - $rawWindow);

        $file->seek($startLine);
        $rawLines = [];
        while (! $file->eof()) {
            $rawLines[] = $file->fgets();
        }

        // Formato real de Monolog en este proyecto: "[Y-m-d H:i:s] canal.NIVEL: mensaje"
        // (fecha con espacio, sin milisegundos ni offset — no confundir con
        // el formato ISO8601 con "T" que usan otras apps Laravel).
        $pattern = '/^\[(?<date>\d{4}-\d{2}-\d{2}[ T][\d:.+-]+)\]\s+\S+\.(?<level>\w+):\s+(?<message>.*)$/';
        $entries = [];
        $current = null;

        foreach ($rawLines as $rawLine) {
            if (preg_match($pattern, $rawLine, $m)) {
                if ($current) {
                    $entries[] = $current;
                }
                $current = [
                    'date' => $m['date'],
                    'level' => strtolower($m['level']),
                    'message' => rtrim($m['message']),
                    'extra' => '',
                ];
            } elseif ($current !== null && trim((string) $rawLine) !== '') {
                // Línea de continuación (stack trace) de la entrada actual.
                $current['extra'] .= $rawLine;
            }
        }
        if ($current) {
            $entries[] = $current;
        }

        return collect($entries)->reverse()->take($maxLines)->values();
    }
}
