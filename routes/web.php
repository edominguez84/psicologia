<?php

use App\Http\Controllers\Api\ChatbotFaqController;
use App\Http\Controllers\Api\ChatbotLeadController;
use App\Http\Controllers\Api\CheckupController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\SiteController;
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

require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
