<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Llamadas automáticas de confirmación de cita (VAPI) — ver
// App\Console\Commands\SendAppointmentCallReminders. El propio comando no
// hace nada si la integración está apagada (VapiCallService::isUsable()),
// así que correrlo cada 5 minutos no tiene costo cuando no se usa.
// Requiere que algo dispare "php artisan schedule:run" con esa frecuencia
// (ver App\Http\Controllers\CronRunnerController y las instrucciones en
// /admin/vapi-settings).
//
// Schedule::call() en vez de Schedule::command(): este último lanza el
// comando como un PROCESO NUEVO del sistema operativo (Symfony\Process,
// que requiere proc_open) — el hosting de producción no tiene proc_open
// habilitado, así que Schedule::command() fallaba en cada ejecución con
// "The Process class relies on proc_open, which is not available on your
// PHP installation", y la llamada automática nunca llegaba a dispararse.
// Schedule::call() con una clausura ejecuta el comando EN EL MISMO
// PROCESO PHP que ya está corriendo (vía Artisan::call()), sin depender
// de proc_open — funciona igual en local y en cualquier hosting.
Schedule::call(fn () => Artisan::call('appointments:call-reminders'))->everyFiveMinutes();
