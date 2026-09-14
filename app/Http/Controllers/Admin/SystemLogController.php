<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\SystemLogReader;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SystemLogController extends Controller
{
    /**
     * Visor de storage/logs/laravel.log para diagnosticar fallos, exclusivo
     * de super_admin. La lectura/parseo vive en App\Support\SystemLogReader,
     * compartida con el informe ejecutivo en PDF (Admin\ReportController).
     */
    public function index(Request $request): View
    {
        $lines = (int) $request->integer('lines', SystemLogReader::DEFAULT_LINES);
        $lines = max(10, min(SystemLogReader::MAX_LINES, $lines));

        $info = SystemLogReader::fileInfo();
        $entries = ($info['exists'] && $info['sizeKb'] > 0) ? SystemLogReader::tailEntries($lines) : collect();

        return view('admin.system-log.index', [
            'entries' => $entries,
            'lines' => $lines,
            'fileExists' => $info['exists'],
            'fileSizeKb' => $info['sizeKb'],
        ]);
    }
}
