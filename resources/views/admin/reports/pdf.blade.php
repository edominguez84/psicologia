<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Informe del sistema</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; color: #1f2a37; font-size: 12px; }
        h1 { font-size: 20px; color: #294a68; margin-bottom: 4px; }
        h2 { font-size: 14px; color: #294a68; margin-top: 24px; margin-bottom: 8px; border-bottom: 1px solid #e3edf7; padding-bottom: 4px; }
        p.meta { color: #4b5a6b; font-size: 11px; margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.metrics td { padding: 3px 0; border-bottom: 1px solid #f4f8fc; }
        table.metrics td.value { text-align: right; font-weight: bold; }
        .grid { width: 100%; }
        .grid td { vertical-align: top; width: 33%; padding-right: 16px; }
        .error-entry { border: 1px solid #e3edf7; border-radius: 6px; padding: 8px; margin-bottom: 8px; }
        .error-level { display: inline-block; background: #f7e9e0; color: #b9744c; font-size: 9px; font-weight: bold; text-transform: uppercase; padding: 2px 6px; border-radius: 8px; }
        .error-date { color: #4b5a6b; font-size: 10px; margin-left: 6px; }
        .error-message { margin-top: 4px; font-family: monospace; font-size: 10px; white-space: pre-wrap; word-wrap: break-word; }
        .empty { color: #4b5a6b; text-align: center; padding: 16px; border: 1px dashed #e3edf7; border-radius: 6px; }
    </style>
</head>
<body>
    <h1>Informe del sistema</h1>
    <p class="meta">Generado el {{ $generatedAt->format('d/m/Y H:i') }}</p>

    <table class="grid">
        <tr>
            <td>
                <h2>Citas por estado</h2>
                <table class="metrics">
                    @foreach ($appointmentsByStatus as $label => $count)
                        <tr><td>{{ $label }}</td><td class="value">{{ $count }}</td></tr>
                    @endforeach
                </table>
            </td>
            <td>
                <h2>Mensajes de contacto</h2>
                <table class="metrics">
                    <tr><td>Sin atender</td><td class="value">{{ $unhandledContacts }}</td></tr>
                    <tr><td>Total</td><td class="value">{{ $totalContacts }}</td></tr>
                </table>
            </td>
            <td>
                <h2>Testimonios</h2>
                <table class="metrics">
                    <tr><td>Pendientes</td><td class="value">{{ $pendingTestimonials }}</td></tr>
                    <tr><td>Aprobados</td><td class="value">{{ $approvedTestimonials }}</td></tr>
                </table>
            </td>
        </tr>
        <tr>
            <td>
                <h2>Usuarios por rol</h2>
                <table class="metrics">
                    @foreach ($usersByRole as $label => $count)
                        <tr><td>{{ $label }}</td><td class="value">{{ $count }}</td></tr>
                    @endforeach
                    <tr><td>Suspendidos</td><td class="value">{{ $bannedUsers }}</td></tr>
                </table>
            </td>
            <td>
                <h2>Chequeos emocionales</h2>
                <table class="metrics">
                    <tr><td>Este mes</td><td class="value">{{ $checkupsThisMonth }}</td></tr>
                </table>
            </td>
            <td></td>
        </tr>
    </table>

    <h2>Errores recientes del sistema</h2>
    @if ($errorEntries->isEmpty())
        <p class="empty">No hay errores registrados en el rango revisado.</p>
    @else
        @foreach ($errorEntries as $entry)
            <div class="error-entry">
                <span class="error-level">{{ $entry['level'] }}</span>
                <span class="error-date">{{ $entry['date'] }}</span>
                <div class="error-message">{{ \Illuminate\Support\Str::limit($entry['message'], 400) }}</div>
            </div>
        @endforeach
    @endif
</body>
</html>
