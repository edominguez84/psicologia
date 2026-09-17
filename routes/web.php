<?php

use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\ChatbotFaqController;
use App\Http\Controllers\Api\ChatbotLeadController;
use App\Http\Controllers\Api\ChatbotMessageController;
use App\Http\Controllers\Api\CheckupController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\CronRunnerController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\Webhooks\TelegramWebhookController;
use App\Http\Controllers\Webhooks\VapiWebhookController;
use App\Http\Controllers\Webhooks\WompiWebhookController;
use App\Http\Middleware\ResolveSiteLocale;
use Illuminate\Support\Facades\Route;

Route::get('/', [SiteController::class, 'home'])->name('home');
Route::get('/privacidad', [SiteController::class, 'privacy'])->name('privacy');
Route::get('/condiciones-de-uso', [SiteController::class, 'terms'])->name('terms');

// Switch de idioma del contenido de fábrica de la landing: solo guarda la
// cookie y redirige de vuelta a donde estaba (o al inicio si no hay
// referer), no cambia nada en el servidor más allá de esa preferencia.
Route::get('/idioma/{locale}', function (string $locale, \Illuminate\Http\Request $request) {
    abort_unless(in_array($locale, ResolveSiteLocale::SUPPORTED, true), 404);

    return redirect($request->headers->get('referer') ?: route('home'))
        ->withCookie(cookie(ResolveSiteLocale::COOKIE_NAME, $locale, 60 * 24 * 365));
})->name('site-locale.switch');

// Endpoints de los formularios (usados por los componentes Vue).
Route::post('/contacto', [ContactController::class, 'store'])
    ->middleware('throttle:6,1')
    ->name('contact.store');

Route::post('/chequeo-emocional', [CheckupController::class, 'store'])
    ->middleware('throttle:12,1')
    ->name('checkup.store');

Route::post('/chatbot-lead', [ChatbotLeadController::class, 'store'])
    ->middleware('throttle:12,1')
    ->name('chatbot-lead.store');

Route::get('/chatbot-faqs', [ChatbotFaqController::class, 'index'])
    ->middleware('throttle:30,1')
    ->name('chatbot-faqs.index');

// Chat en vivo con IA del widget web (ver ChatbotWidget.vue) — mismo motor
// (ChatbotAiService) que usa el bot de Telegram, identificando al visitante
// por su sesión de navegador en vez de un chat_id externo.
Route::post('/chatbot-mensaje', [ChatbotMessageController::class, 'store'])
    ->middleware('throttle:20,1')
    ->name('chatbot-message.store');

// Clic en un link de red social del footer, vía navigator.sendBeacon —
// ver partials/social-icons.blade.php y App\Http\Controllers\Api\AnalyticsController.
Route::post('/analytics/social-click', [AnalyticsController::class, 'socialClick'])
    ->middleware('throttle:30,1')
    ->name('analytics.social-click');

// Notificación de pago de Wompi: ruta pública sin CSRF (excepción declarada
// en bootstrap/app.php, viene de un servidor externo, no de un navegador con
// sesión) — se autentica verificando el header 'wompi_hash' dentro del
// propio controlador. Ver App\Http\Controllers\Webhooks\WompiWebhookController.
Route::post('/webhooks/wompi', WompiWebhookController::class)->name('webhooks.wompi');

// Mensajes entrantes del bot de Telegram — ver App\Http\Controllers\Webhooks\TelegramWebhookController.
// Sin throttle por IP a propósito: todos los mensajes llegan desde los
// servidores de Telegram (misma IP/rango para todos los pacientes), así que
// un límite aquí penalizaría a todo el mundo junto en vez de a quien abusa.
// El límite de uso de IA real es por conversación individual, ver
// App\Models\ChatbotConversation::hasReachedDailyAiLimit().
Route::post('/webhooks/telegram', TelegramWebhookController::class)->name('webhooks.telegram');

// Resultado de las llamadas automáticas de confirmación de cita (VAPI) —
// ver App\Http\Controllers\Webhooks\VapiWebhookController.
Route::post('/webhooks/vapi', VapiWebhookController::class)->name('webhooks.vapi');

// Dispara el scheduler (routes/console.php) por HTTP — ver
// App\Http\Controllers\CronRunnerController. Pensado para un servicio
// externo tipo cron-job.org, no para el cron nativo del hosting.
Route::get('/cron/run-scheduler', CronRunnerController::class)->name('cron.run-scheduler');

require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
