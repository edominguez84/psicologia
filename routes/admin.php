<?php

use App\Http\Controllers\Admin\AboutPhotoController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AppointmentController;
use App\Http\Controllers\Admin\AppointmentSlotController;
use App\Http\Controllers\Admin\ChatbotFaqController;
use App\Http\Controllers\Admin\ContactFormSettingsController;
use App\Http\Controllers\Admin\ContactSettingsController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\CustomSectionController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FaviconController;
use App\Http\Controllers\Admin\GalleryController;
use App\Http\Controllers\Admin\LegalPageController;
use App\Http\Controllers\Admin\LogoController;
use App\Http\Controllers\Admin\MessagesController;
use App\Http\Controllers\Admin\PaymentSettingsController;
use App\Http\Controllers\Admin\ProfanityFilterController;
use App\Http\Controllers\Admin\PromotionController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SectionVisibilityController;
use App\Http\Controllers\Admin\SecurityController;
use App\Http\Controllers\Admin\SocialLinksController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\SystemLogController;
use App\Http\Controllers\Admin\TestimonialController;
use App\Http\Controllers\Admin\ThemeController;
use App\Http\Controllers\Admin\UsersController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'banned', 'admin', 'session.idle'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/theme', [ThemeController::class, 'edit'])->name('theme.edit');
    Route::put('/theme', [ThemeController::class, 'update'])->name('theme.update');
    Route::post('/theme/preset', [ThemeController::class, 'applyPreset'])->name('theme.preset');

    Route::get('/content/{section}', [ContentController::class, 'edit'])->name('content.edit');
    Route::put('/content/{section}', [ContentController::class, 'update'])->name('content.update');

    Route::get('/contact', [ContactSettingsController::class, 'edit'])->name('contact.edit');
    Route::put('/contact', [ContactSettingsController::class, 'update'])->name('contact.update');

    Route::get('/contact-form', [ContactFormSettingsController::class, 'edit'])->name('contact-form.edit');
    Route::put('/contact-form', [ContactFormSettingsController::class, 'update'])->name('contact-form.update');
    Route::post('/contact-form/custom-fields', [ContactFormSettingsController::class, 'storeCustomField'])->name('contact-form.custom-fields.store');
    Route::delete('/contact-form/custom-fields/{key}', [ContactFormSettingsController::class, 'destroyCustomField'])->name('contact-form.custom-fields.destroy');

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

    Route::get('/security', [SecurityController::class, 'edit'])->name('security.edit');
    Route::put('/security', [SecurityController::class, 'update'])->name('security.update');

    Route::get('/section-visibility', [SectionVisibilityController::class, 'edit'])->name('section-visibility.edit');
    Route::put('/section-visibility', [SectionVisibilityController::class, 'update'])->name('section-visibility.update');

    Route::get('/appointment-slots', [AppointmentSlotController::class, 'index'])->name('appointment-slots.index');
    Route::post('/appointment-slots', [AppointmentSlotController::class, 'store'])->name('appointment-slots.store');
    Route::patch('/appointment-slots/{appointmentSlot}/toggle', [AppointmentSlotController::class, 'toggle'])->name('appointment-slots.toggle');
    Route::delete('/appointment-slots/{appointmentSlot}', [AppointmentSlotController::class, 'destroy'])->name('appointment-slots.destroy');

    Route::get('/appointments', [AppointmentController::class, 'index'])->name('appointments.index');
    Route::patch('/appointments/{appointment}/approve', [AppointmentController::class, 'approve'])->name('appointments.approve');
    Route::patch('/appointments/{appointment}/reject', [AppointmentController::class, 'reject'])->name('appointments.reject');
    Route::patch('/appointments/{appointment}/confirm-payment', [AppointmentController::class, 'confirmPayment'])->name('appointments.confirm-payment');

    Route::get('/custom-sections', [CustomSectionController::class, 'index'])->name('custom-sections.index');
    Route::post('/custom-sections', [CustomSectionController::class, 'store'])->name('custom-sections.store');
    Route::get('/custom-sections/{customSection}/edit', [CustomSectionController::class, 'edit'])->name('custom-sections.edit');
    Route::put('/custom-sections/{customSection}', [CustomSectionController::class, 'update'])->name('custom-sections.update');
    Route::patch('/custom-sections/{customSection}/toggle', [CustomSectionController::class, 'toggle'])->name('custom-sections.toggle');
    Route::put('/custom-sections-reorder', [CustomSectionController::class, 'reorder'])->name('custom-sections.reorder');
    Route::delete('/custom-sections/{customSection}', [CustomSectionController::class, 'destroy'])->name('custom-sections.destroy');

    // A partir de aquí, todo reservado a la super administradora: gestión de
    // usuarios (banear/cambiar rol), promociones y planes, preguntas del
    // chatbot, alta de staff, páginas legales, auditoría/logs, pagos,
    // testimonios y filtro de contenido, e informes.
    Route::middleware('super_admin')->group(function () {
        Route::get('/users', [UsersController::class, 'index'])->name('users.index');
        Route::patch('/users/{user}/ban', [UsersController::class, 'ban'])->name('users.ban');
        Route::patch('/users/{user}/unban', [UsersController::class, 'unban'])->name('users.unban');
        Route::patch('/users/{user}/role', [UsersController::class, 'updateRole'])->name('users.role');

        Route::get('/promotions', [PromotionController::class, 'index'])->name('promotions.index');
        Route::post('/promotions', [PromotionController::class, 'store'])->name('promotions.store');
        Route::put('/promotions/{promotion}', [PromotionController::class, 'update'])->name('promotions.update');
        Route::patch('/promotions/{promotion}/toggle', [PromotionController::class, 'toggle'])->name('promotions.toggle');
        Route::put('/promotions-reorder', [PromotionController::class, 'reorder'])->name('promotions.reorder');
        Route::delete('/promotions/{promotion}', [PromotionController::class, 'destroy'])->name('promotions.destroy');

        Route::get('/chatbot-faqs', [ChatbotFaqController::class, 'index'])->name('chatbot-faqs.index');
        Route::put('/chatbot-faqs-settings', [ChatbotFaqController::class, 'updateSettings'])->name('chatbot-faqs.settings.update');
        Route::post('/chatbot-faqs', [ChatbotFaqController::class, 'store'])->name('chatbot-faqs.store');
        Route::put('/chatbot-faqs/{chatbotFaq}', [ChatbotFaqController::class, 'update'])->name('chatbot-faqs.update');
        Route::patch('/chatbot-faqs/{chatbotFaq}/toggle', [ChatbotFaqController::class, 'toggle'])->name('chatbot-faqs.toggle');
        Route::put('/chatbot-faqs-reorder', [ChatbotFaqController::class, 'reorder'])->name('chatbot-faqs.reorder');
        Route::delete('/chatbot-faqs/{chatbotFaq}', [ChatbotFaqController::class, 'destroy'])->name('chatbot-faqs.destroy');

        // Alta manual de cuentas de staff (o pacientes) — reservado a la
        // super administradora.
        Route::get('/staff/create', [StaffController::class, 'create'])->name('staff.create');
        Route::post('/staff', [StaffController::class, 'store'])->name('staff.store');

        // Icono del sitio (favicon) — reservado a la super administradora.
        Route::get('/favicon', [FaviconController::class, 'edit'])->name('favicon.edit');
        Route::post('/favicon', [FaviconController::class, 'update'])->name('favicon.update');
        Route::delete('/favicon', [FaviconController::class, 'destroy'])->name('favicon.destroy');

        // Páginas legales (privacidad, condiciones de uso) — reservado a la
        // super administradora.
        Route::get('/legal/{page}', [LegalPageController::class, 'edit'])->name('legal.edit');
        Route::put('/legal/{page}', [LegalPageController::class, 'update'])->name('legal.update');

        // Registro de auditoría y logs del sistema — reservado a la super
        // administradora.
        Route::get('/activity-log', [ActivityLogController::class, 'index'])->name('activity-log.index');
        Route::get('/system-log', [SystemLogController::class, 'index'])->name('system-log.index');

        // Métodos de pago (Wompi/transferencia bancaria) — reservado a la
        // super administradora.
        Route::get('/payment-settings', [PaymentSettingsController::class, 'edit'])->name('payment-settings.edit');
        Route::put('/payment-settings', [PaymentSettingsController::class, 'update'])->name('payment-settings.update');
        Route::post('/payment-settings/test-wompi', [PaymentSettingsController::class, 'testWompi'])->name('payment-settings.test-wompi');

        // Aprobación de testimonios y filtro de contenido — reservado a la
        // super administradora (antes cualquier admin podía aprobar).
        Route::get('/testimonials', [TestimonialController::class, 'index'])->name('testimonials.index');
        Route::patch('/testimonials/{testimonial}/approve', [TestimonialController::class, 'approve'])->name('testimonials.approve');
        Route::patch('/testimonials/{testimonial}/unapprove', [TestimonialController::class, 'unapprove'])->name('testimonials.unapprove');
        Route::delete('/testimonials/{testimonial}', [TestimonialController::class, 'destroy'])->name('testimonials.destroy');

        Route::get('/profanity-filter', [ProfanityFilterController::class, 'edit'])->name('profanity-filter.edit');
        Route::put('/profanity-filter', [ProfanityFilterController::class, 'update'])->name('profanity-filter.update');

        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/download', [ReportController::class, 'downloadPdf'])->name('reports.download');
    });
});
