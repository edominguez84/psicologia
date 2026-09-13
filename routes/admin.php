<?php

use App\Http\Controllers\Admin\AboutPhotoController;
use App\Http\Controllers\Admin\ChatbotFaqController;
use App\Http\Controllers\Admin\ContactSettingsController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GalleryController;
use App\Http\Controllers\Admin\LogoController;
use App\Http\Controllers\Admin\MessagesController;
use App\Http\Controllers\Admin\SectionVisibilityController;
use App\Http\Controllers\Admin\SecurityController;
use App\Http\Controllers\Admin\SocialLinksController;
use App\Http\Controllers\Admin\ThemeController;
use App\Http\Controllers\Admin\UsersController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'banned', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/theme', [ThemeController::class, 'edit'])->name('theme.edit');
    Route::put('/theme', [ThemeController::class, 'update'])->name('theme.update');

    Route::get('/content/{section}', [ContentController::class, 'edit'])->name('content.edit');
    Route::put('/content/{section}', [ContentController::class, 'update'])->name('content.update');

    Route::get('/contact', [ContactSettingsController::class, 'edit'])->name('contact.edit');
    Route::put('/contact', [ContactSettingsController::class, 'update'])->name('contact.update');

    Route::get('/messages', [MessagesController::class, 'index'])->name('messages.index');
    Route::patch('/messages/contact/{contactMessage}/handle', [MessagesController::class, 'handleContact'])->name('messages.contact.handle');
    Route::patch('/messages/chatbot/{chatbotLead}/handle', [MessagesController::class, 'handleChatbotLead'])->name('messages.chatbot.handle');

    Route::get('/logo', [LogoController::class, 'edit'])->name('logo.edit');
    Route::post('/logo', [LogoController::class, 'update'])->name('logo.update');
    Route::delete('/logo', [LogoController::class, 'destroy'])->name('logo.destroy');

    Route::get('/social', [SocialLinksController::class, 'edit'])->name('social.edit');
    Route::put('/social', [SocialLinksController::class, 'update'])->name('social.update');

    Route::get('/about-photo', [AboutPhotoController::class, 'edit'])->name('about-photo.edit');
    Route::post('/about-photo', [AboutPhotoController::class, 'update'])->name('about-photo.update');
    Route::delete('/about-photo', [AboutPhotoController::class, 'destroy'])->name('about-photo.destroy');

    Route::get('/gallery', [GalleryController::class, 'edit'])->name('gallery.edit');
    Route::post('/gallery', [GalleryController::class, 'store'])->name('gallery.store');
    Route::put('/gallery', [GalleryController::class, 'update'])->name('gallery.update');
    Route::delete('/gallery/{index}', [GalleryController::class, 'destroy'])->name('gallery.destroy');

    Route::get('/users', [UsersController::class, 'index'])->name('users.index');
    Route::patch('/users/{user}/ban', [UsersController::class, 'ban'])->name('users.ban');
    Route::patch('/users/{user}/unban', [UsersController::class, 'unban'])->name('users.unban');
    Route::patch('/users/{user}/role', [UsersController::class, 'updateRole'])->name('users.role');

    Route::get('/security', [SecurityController::class, 'edit'])->name('security.edit');
    Route::put('/security', [SecurityController::class, 'update'])->name('security.update');

    Route::get('/section-visibility', [SectionVisibilityController::class, 'edit'])->name('section-visibility.edit');
    Route::put('/section-visibility', [SectionVisibilityController::class, 'update'])->name('section-visibility.update');

    // Preguntas del chatbot: reservado a la super administradora.
    Route::middleware('super_admin')->group(function () {
        Route::get('/chatbot-faqs', [ChatbotFaqController::class, 'index'])->name('chatbot-faqs.index');
        Route::post('/chatbot-faqs', [ChatbotFaqController::class, 'store'])->name('chatbot-faqs.store');
        Route::put('/chatbot-faqs/{chatbotFaq}', [ChatbotFaqController::class, 'update'])->name('chatbot-faqs.update');
        Route::patch('/chatbot-faqs/{chatbotFaq}/toggle', [ChatbotFaqController::class, 'toggle'])->name('chatbot-faqs.toggle');
        Route::put('/chatbot-faqs-reorder', [ChatbotFaqController::class, 'reorder'])->name('chatbot-faqs.reorder');
        Route::delete('/chatbot-faqs/{chatbotFaq}', [ChatbotFaqController::class, 'destroy'])->name('chatbot-faqs.destroy');
    });
});
