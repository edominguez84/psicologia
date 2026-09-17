<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;

/**
 * Dispara el scheduler de Laravel (routes/console.php) por HTTP, para
 * hostings compartidos cuyo panel de cron nativo no permite programar una
 * frecuencia de minutos real (ver Hostinger hPanel, cuya interfaz de cron
 * obliga a fijar mes y día de la semana como valores únicos, no "todos").
 * Un servicio externo gratuito (cron-job.org) le pega a esta URL cada 5
 * minutos en lugar del cron del hosting — el propio schedule:run decide
 * qué tareas definidas realmente le toca correr en cada llamada, así que
 * pegarle de más no hace nada extra (ver App\Console\Commands\
 * SendAppointmentCallReminders, que ya no hace nada si VAPI está apagado).
 *
 * Autenticidad: el secreto viaja en la propia URL (?secret=...) porque
 * cron-job.org y servicios similares llaman por GET simple, sin poder
 * mandar headers personalizados en el plan gratuito — se compara con
 * hash_equals contra config('services.cron_runner.secret'), mismo criterio
 * de secreto compartido que el resto de webhooks del sitio.
 */
class CronRunnerController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $secret = config('services.cron_runner.secret');

        if (! $secret || ! hash_equals($secret, (string) $request->query('secret'))) {
            abort(403, 'Secreto inválido.');
        }

        Artisan::call('schedule:run');

        return response('ok', 200);
    }
}
