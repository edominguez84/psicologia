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
// Requiere que el hosting tenga un cron real apuntando a
// "php artisan schedule:run" cada minuto (ver el resto de instrucciones de
// configuración en /admin/vapi-settings).
Schedule::command('appointments:call-reminders')->everyFiveMinutes();
