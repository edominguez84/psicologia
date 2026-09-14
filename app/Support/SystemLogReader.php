<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * Lee y parsea storage/logs/laravel.log, compartido entre el visor
 * "Logs del sistema" (Admin\SystemLogController) y el informe ejecutivo en
 * PDF (Admin\ReportController) — antes vivía solo dentro del controlador del
 * visor, se extrajo aquí para no duplicar la lógica de parseo.
 */
class SystemLogReader
{
    public const MAX_LINES = 500;
    public const DEFAULT_LINES = 200;

    public static function fileInfo(): array
    {
        $path = storage_path('logs/laravel.log');
        $exists = is_file($path);

        return [
            'path' => $path,
            'exists' => $exists,
            'sizeKb' => $exists ? (int) round(filesize($path) / 1024) : 0,
        ];
    }

    /**
     * Lee las últimas $maxLines "entradas" del log (una entrada = una línea
     * que empieza con "[fecha]", seguida de todas las líneas de stack trace
     * que le pertenecen) usando SplFileObject::seek() para ir directo al
     * final sin recorrer todo el archivo primero.
     */
    public static function tailEntries(int $maxLines = self::DEFAULT_LINES): Collection
    {
        $maxLines = max(10, min(self::MAX_LINES, $maxLines));
        $info = self::fileInfo();

        if (! $info['exists'] || $info['sizeKb'] === 0) {
            return collect();
        }

        $file = new \SplFileObject($info['path'], 'r');
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

    /**
     * Solo las entradas de nivel error/critical/alert/emergency, para el
     * resumen del informe ejecutivo en PDF.
     */
    public static function errorEntries(int $maxLines = self::DEFAULT_LINES): Collection
    {
        return self::tailEntries($maxLines)
            ->whereIn('level', ['error', 'critical', 'alert', 'emergency'])
            ->values();
    }
}
