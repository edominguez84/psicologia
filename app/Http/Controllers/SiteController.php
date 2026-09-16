<?php

namespace App\Http\Controllers;

use App\Models\AnalyticsEvent;
use App\Models\CustomSection;
use App\Models\Testimonial;
use App\Services\SiteSettingsService;
use App\Support\HtmlSanitizer;
use Illuminate\Support\Facades\Session;

class SiteController extends Controller
{
    public function __construct(private SiteSettingsService $settings)
    {
    }

    public function home()
    {
        // Modo demo (Netlify sin backend): sin BD, sin secciones
        // personalizadas ni testimonios de pacientes.
        $demoMode = (bool) config('site.demo_mode');

        if (! $demoMode) {
            $this->recordPageView();
        }

        $customSections = $demoMode ? collect() : CustomSection::active()->ordered()->get();

        // Testimonios: los de config/site.php (curados por la propia
        // psicóloga) más los aprobados de pacientes registrados. Nunca se
        // muestran los no aprobados.
        $patientTestimonials = $demoMode
            ? collect()
            : Testimonial::approved()->with('user')->latest()->get()->map(fn ($t) => [
                'name' => $t->user->name,
                'place' => null,
                'text' => $t->text,
                'rating' => $t->rating,
            ]);

        return view('home', [
            'site' => config('site'),
            'customSections' => $customSections,
            'patientTestimonials' => $patientTestimonials,
        ]);
    }

    public function privacy()
    {
        return $this->legalPage('privacy', 'Política de privacidad', $this->defaultPrivacyHtml());
    }

    public function terms()
    {
        return $this->legalPage('terms', 'Condiciones de uso', $this->defaultTermsHtml());
    }

    /**
     * Ambas páginas legales comparten la misma vista y el mismo mecanismo:
     * se muestra lo guardado desde /admin/legal/{page} si existe, o si no un
     * texto por defecto razonable (para no dejar la página en blanco antes
     * de que alguien la edite por primera vez). El HTML se sanitiza de nuevo
     * aquí como defensa en profundidad, aunque ya se sanitizó al guardarse.
     */
    private function legalPage(string $page, string $defaultTitle, string $defaultHtml)
    {
        $saved = $this->settings->get('legal_pages')[$page] ?? null;

        return view('legal-page', [
            'site' => config('site'),
            'pageTitle' => $saved['title'] ?? $defaultTitle,
            'bodyHtml' => HtmlSanitizer::clean($saved['body_html'] ?? $defaultHtml),
        ]);
    }

    private function defaultPrivacyHtml(): string
    {
        $name = config('site.name');
        $role = config('site.role');
        $registration = config('site.registration');
        $email = config('site.contact.email');
        $disclaimer = config('site.footer.disclaimer');

        return <<<HTML
            <p>Esta web tiene carácter informativo sobre los servicios de psicología online de {$name}. A continuación se explica cómo se tratan los datos que facilitas a través de los formularios de contacto y del chequeo de bienestar emocional.</p>
            <h2>Responsable del tratamiento</h2>
            <p>{$name} — {$role} ({$registration}). Puedes contactar en <a href="mailto:{$email}">{$email}</a>.</p>
            <h2>Qué datos se recogen y con qué finalidad</h2>
            <ul>
                <li><strong>Formulario de contacto:</strong> nombre, email, teléfono (opcional) y el mensaje que escribas. Finalidad: responder a tu consulta y, en su caso, coordinar una primera cita.</li>
                <li><strong>Chequeo de bienestar emocional:</strong> tus respuestas (anónimas) y, opcionalmente, tu email si pides que te escriba. Finalidad: mostrarte un resultado orientativo y, si lo solicitas, ponerme en contacto contigo.</li>
                <li><strong>Datos técnicos:</strong> dirección IP y navegador, con la única finalidad de prevenir el envío masivo de spam.</li>
            </ul>
            <h2>Base legal</h2>
            <p>El tratamiento se basa en tu consentimiento, que otorgas al marcar la casilla correspondiente y enviar el formulario, y en el interés legítimo de atender tu solicitud.</p>
            <h2>Conservación y cesiones</h2>
            <p>Los datos se conservan el tiempo necesario para atender tu solicitud y, si no llega a iniciarse un proceso terapéutico, se eliminan pasado un plazo razonable. <strong>No se ceden a terceros</strong> salvo obligación legal.</p>
            <h2>Tus derechos</h2>
            <p>Puedes ejercer tus derechos de acceso, rectificación, supresión, oposición, limitación y portabilidad escribiendo a <a href="mailto:{$email}">{$email}</a>.</p>
            <h2>Aviso importante</h2>
            <p>{$disclaimer}</p>
            HTML;
    }

    /**
     * Registra una visita a la home, deduplicada por sesión: si esta misma
     * sesión de navegador ya generó un page_view, no cuenta un refresco o
     * navegación interna como visita nueva. No usa cookies de terceros ni
     * guarda IP — solo el id de sesión de Laravel (ya existente para CSRF/
     * auth), que rota cuando el navegador borra cookies o pasa el tiempo.
     */
    private function recordPageView(): void
    {
        if (Session::get('analytics_page_view_recorded')) {
            return;
        }

        try {
            AnalyticsEvent::create([
                'type' => 'page_view',
                'session_id' => Session::getId(),
            ]);
            Session::put('analytics_page_view_recorded', true);
        } catch (\Throwable $e) {
            // No debe romper la carga de la home si la tabla no existe
            // todavía (instalación en frío antes de migrar) o la BD falla.
        }
    }

    private function defaultTermsHtml(): string
    {
        $name = config('site.name');
        $role = config('site.role');
        $email = config('site.contact.email');
        $disclaimer = config('site.footer.disclaimer');

        return <<<HTML
            <p>Estas condiciones de uso regulan el acceso y uso de este sitio web, propiedad de {$name} — {$role}. Al navegar por esta web aceptas estas condiciones.</p>
            <h2>Uso del sitio</h2>
            <p>Esta web tiene carácter informativo. El contenido no sustituye una evaluación ni un tratamiento psicológico profesional individualizado.</p>
            <h2>Propiedad intelectual</h2>
            <p>Los textos, imágenes y demás contenidos de este sitio son propiedad de {$name} o se usan con la debida autorización, y no pueden reproducirse sin permiso.</p>
            <h2>Responsabilidad</h2>
            <p>{$disclaimer}</p>
            <h2>Contacto</h2>
            <p>Para cualquier duda sobre estas condiciones, puedes escribir a <a href="mailto:{$email}">{$email}</a>.</p>
            HTML;
    }
}
