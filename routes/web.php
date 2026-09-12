<?php

use App\Http\Controllers\Api\ChatbotFaqController;
use App\Http\Controllers\Api\ChatbotLeadController;
use App\Http\Controllers\Api\CheckupController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SiteController::class, 'home'])->name('home');
Route::get('/privacidad', [SiteController::class, 'privacy'])->name('privacy');

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
