<?php

namespace App\Support;

/**
 * Catálogo de "features" del panel de administración cuya visibilidad por
 * rol de staff es configurable por el super_admin (ver App\Models\Role,
 * Admin\RoleManagementController). Cada feature agrupa una o más rutas bajo
 * una sola llave — activar/desactivar una feature activa/desactiva todas
 * sus rutas juntas (p. ej. "Promociones" cubre listar, crear, editar,
 * reordenar y eliminar con una sola llave, no cinco).
 *
 * Cada feature tiene un 'default':
 * - true: las 15 secciones que ya eran visibles para cualquier staff antes
 *   de que este sistema existiera (apariencia, contenido, citas, etc.) —
 *   siguen encendidas de fábrica para no quitarle nada a nadie.
 * - false: las secciones que hasta ahora eran exclusivas de super_admin
 *   (chatbot, pagos, roles, usuarios, etc.). Aparecen en el catálogo para
 *   que el super_admin pueda delegarlas puntual y temporalmente a un admin
 *   (por eso se listan siempre, con su checkbox, en /admin/roles), pero
 *   nacen apagadas — nadie las tiene salvo que el super_admin las active a
 *   propósito.
 *
 * 'super_admin' nunca pasa por este sistema — siempre ve todo, sin
 * excepción, tenga o no fila de permisos guardada.
 */
class AdminPermissions
{
    public static function all(): array
    {
        return [
            'theme' => ['label' => 'Apariencia', 'routes' => ['admin.theme.*'], 'default' => true],
            'section-visibility' => ['label' => 'Visibilidad de secciones', 'routes' => ['admin.section-visibility.*'], 'default' => true],
            'custom-sections' => ['label' => 'Secciones personalizadas', 'routes' => ['admin.custom-sections.*'], 'default' => true],
            'logo' => ['label' => 'Logo', 'routes' => ['admin.logo.*'], 'default' => true],
            'about-photo' => ['label' => 'Foto de portada', 'routes' => ['admin.about-photo.*'], 'default' => true],
            'gallery' => ['label' => 'Galería', 'routes' => ['admin.gallery.*'], 'default' => true],
            'social' => ['label' => 'Redes sociales', 'routes' => ['admin.social.*'], 'default' => true],
            'contact' => ['label' => 'Contacto', 'routes' => ['admin.contact.*'], 'default' => true],
            'contact-form' => ['label' => 'Formulario de contacto', 'routes' => ['admin.contact-form.*'], 'default' => true],
            'content' => ['label' => 'Textos del sitio', 'routes' => ['admin.content.*'], 'default' => true],
            'messages' => ['label' => 'Mensajes', 'routes' => ['admin.messages.*'], 'default' => true],
            'appointment-slots' => ['label' => 'Horarios de citas', 'routes' => ['admin.appointment-slots.*'], 'default' => true],
            'appointments' => ['label' => 'Citas', 'routes' => ['admin.appointments.*'], 'default' => true],
            'call-slots' => ['label' => 'Horarios de llamada gratis', 'routes' => ['admin.call-slots.*'], 'default' => true],
            'security' => ['label' => 'Seguridad', 'routes' => ['admin.security.*'], 'default' => true],

            // Antes exclusivas de super_admin — delegables bajo pedido, apagadas de fábrica.
            'users' => ['label' => 'Usuarios (banear, cambiar rol)', 'routes' => ['admin.users.*'], 'default' => false],
            'staff' => ['label' => 'Alta de cuentas de staff', 'routes' => ['admin.staff.*'], 'default' => false],
            'roles' => ['label' => 'Gestión de roles y permisos', 'routes' => ['admin.roles.*'], 'default' => false],
            'promotions' => ['label' => 'Promociones y planes', 'routes' => ['admin.promotions.*'], 'default' => false],
            'chatbot-faqs' => ['label' => 'Preguntas del chatbot', 'routes' => ['admin.chatbot-faqs.*'], 'default' => false],
            'chatbot-channels' => ['label' => 'Credenciales de chatbot (Telegram, WhatsApp, Facebook, IA)', 'routes' => ['admin.chatbot-channels.*'], 'default' => false],
            'favicon' => ['label' => 'Icono del sitio (favicon)', 'routes' => ['admin.favicon.*'], 'default' => false],
            'legal' => ['label' => 'Páginas legales', 'routes' => ['admin.legal.*'], 'default' => false],
            'activity-log' => ['label' => 'Auditoría y logs del sistema', 'routes' => ['admin.activity-log.*', 'admin.system-log.*'], 'default' => false],
            'payment-settings' => ['label' => 'Métodos de pago (Wompi, transferencia)', 'routes' => ['admin.payment-settings.*'], 'default' => false],
            'testimonials' => ['label' => 'Aprobación de testimonios', 'routes' => ['admin.testimonials.*'], 'default' => false],
            'profanity-filter' => ['label' => 'Filtro de contenido', 'routes' => ['admin.profanity-filter.*'], 'default' => false],
            'reports' => ['label' => 'Informes', 'routes' => ['admin.reports.*'], 'default' => false],
            'analytics' => ['label' => 'Panel de analíticas', 'routes' => ['admin.analytics.*'], 'default' => false],
            'system-manual' => ['label' => 'Manual del sistema', 'routes' => ['admin.system-manual.*'], 'default' => false],
            'vapi-settings' => ['label' => 'Llamadas automáticas de confirmación (VAPI)', 'routes' => ['admin.vapi-settings.*'], 'default' => false],
        ];
    }

    /**
     * Llaves del catálogo con su valor de fábrica — así un rol recién creado
     * (o antes de que el super_admin toque esta configuración por primera
     * vez) ve exactamente lo mismo que veía antes de que este sistema
     * existiera: las 15 secciones históricas encendidas, y las secciones
     * antes exclusivas de super_admin apagadas hasta que se deleguen.
     */
    public static function defaultsFor(string $role): array
    {
        return array_map(fn ($feature) => $feature['default'], self::all());
    }
}
