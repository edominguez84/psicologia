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
 * 'super_admin' y las rutas fuera de este catálogo (ver, Mi seguridad, etc.)
 * nunca pasan por este sistema — siguen las reglas de acceso ya existentes
 * (middleware 'admin'/'super_admin'), este catálogo solo añade una capa
 * adicional de restricción para roles de staff no-super_admin sobre lo que
 * YA podían ver.
 */
class AdminPermissions
{
    public static function all(): array
    {
        return [
            'theme' => ['label' => 'Apariencia', 'routes' => ['admin.theme.*']],
            'section-visibility' => ['label' => 'Visibilidad de secciones', 'routes' => ['admin.section-visibility.*']],
            'custom-sections' => ['label' => 'Secciones personalizadas', 'routes' => ['admin.custom-sections.*']],
            'logo' => ['label' => 'Logo', 'routes' => ['admin.logo.*']],
            'about-photo' => ['label' => 'Foto de portada', 'routes' => ['admin.about-photo.*']],
            'gallery' => ['label' => 'Galería', 'routes' => ['admin.gallery.*']],
            'social' => ['label' => 'Redes sociales', 'routes' => ['admin.social.*']],
            'contact' => ['label' => 'Contacto', 'routes' => ['admin.contact.*']],
            'contact-form' => ['label' => 'Formulario de contacto', 'routes' => ['admin.contact-form.*']],
            'content' => ['label' => 'Textos del sitio', 'routes' => ['admin.content.*']],
            'messages' => ['label' => 'Mensajes', 'routes' => ['admin.messages.*']],
            'appointment-slots' => ['label' => 'Horarios de citas', 'routes' => ['admin.appointment-slots.*']],
            'appointments' => ['label' => 'Citas', 'routes' => ['admin.appointments.*']],
            'call-slots' => ['label' => 'Horarios de llamada gratis', 'routes' => ['admin.call-slots.*']],
            'security' => ['label' => 'Seguridad', 'routes' => ['admin.security.*']],
        ];
    }

    /**
     * Todas las llaves del catálogo, habilitadas por defecto — así un rol
     * recién creado (o antes de que el super_admin toque esta configuración
     * por primera vez) sigue viendo exactamente lo mismo que veía antes de
     * que este sistema existiera.
     */
    public static function defaultsFor(string $role): array
    {
        return array_fill_keys(array_keys(self::all()), true);
    }
}
