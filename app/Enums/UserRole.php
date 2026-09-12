<?php

namespace App\Enums;

/**
 * Roles simples como string, sin tabla de permisos aparte. 'editor' queda
 * reservado para el futuro: hoy tiene el mismo acceso que 'admin' porque
 * ninguna ruta distingue permisos más finos todavía — se amplía cuando haga
 * falta, no antes.
 */
enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case Editor = 'editor';
    // Rol base sin acceso al panel admin (valor por defecto de la columna).
    case User = 'user';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super administrador',
            self::Admin => 'Administrador',
            self::Editor => 'Editor',
            self::User => 'Usuario',
        };
    }
}
