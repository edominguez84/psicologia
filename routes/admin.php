<?php

use App\Http\Controllers\Admin\ContactSettingsController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LogoController;
use App\Http\Controllers\Admin\MessagesController;
use App\Http\Controllers\Admin\ThemeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/theme', [ThemeController::class, 'edit'])->name('theme.edit');
    Route::put('/theme', [ThemeController::class, 'update'])->name('theme.update');

    Route::get('/content/{section}', [ContentController::class, 'edit'])->name('content.edit');
    Route::put('/content/{section}', [ContentController::class, 'update'])->name('content.update');

    Route::get('/contact', [ContactSettingsController::class, 'edit'])->name('contact.edit');
    Route::put('/contact', [ContactSettingsController::class, 'update'])->name('contact.update');

    Route::get('/messages', [MessagesController::class, 'index'])->name('messages.index');
    Route::patch('/messages/contact/{contactMessage}/handle', [MessagesController::class, 'handleContact'])->name('messages.contact.handle');

    Route::get('/logo', [LogoController::class, 'edit'])->name('logo.edit');
    Route::post('/logo', [LogoController::class, 'update'])->name('logo.update');
    Route::delete('/logo', [LogoController::class, 'destroy'])->name('logo.destroy');
});
