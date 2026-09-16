<?php

use App\Support\AdminPermissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            // 'slug' es el valor real guardado en users.role (mismo string
            // que ya usaba App\Enums\UserRole: 'super_admin', 'admin', etc.)
            // — se conserva sin cambios para que ninguna cuenta existente
            // necesite migrarse de valor.
            $table->string('slug')->unique();
            $table->string('name');
            // Los 5 roles con los que el sitio ya operaba no se pueden
            // eliminar ni renombrar el slug — son la base que el código
            // sigue asumiendo en varios puntos (isSuperAdmin(), el registro
            // público siempre crea 'patient', etc.). Roles creados después
            // por el super_admin sí se pueden eliminar.
            $table->boolean('is_system')->default(false);
            // Si es un rol de "staff" (entra al panel admin) o de "cuenta"
            // (paciente/usuario base, sin panel) — decide en qué formularios
            // aparece como opción (Crear cuenta ofrece roles de staff,
            // el registro público siempre asigna 'patient' sin elegir).
            $table->boolean('is_staff')->default(true);
            // Permisos del panel para este rol: mismo shape que ya usaba
            // site_settings.admin_role_permissions.{admin|editor} —
            // {feature_key: bool, ...} contra el catálogo de
            // App\Support\AdminPermissions. Null para super_admin (no
            // aplica, siempre ve todo) y para roles no-staff.
            $table->json('permissions')->nullable();
            $table->timestamps();
        });

        // Siembra los 5 roles que ya existían como enum — cualquier cuenta
        // con esos valores en users.role sigue funcionando exactamente
        // igual, ahora contra una fila real en vez de un caso de enum.
        $now = now();
        $allFeatures = array_fill_keys(array_keys(AdminPermissions::all()), true);

        DB::table('roles')->insert([
            ['slug' => 'super_admin', 'name' => 'Super administrador', 'is_system' => true, 'is_staff' => true, 'permissions' => null, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'admin', 'name' => 'Administrador', 'is_system' => true, 'is_staff' => true, 'permissions' => json_encode($allFeatures), 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'editor', 'name' => 'Editor', 'is_system' => true, 'is_staff' => true, 'permissions' => json_encode($allFeatures), 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'patient', 'name' => 'Paciente', 'is_system' => true, 'is_staff' => false, 'permissions' => null, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'user', 'name' => 'Usuario', 'is_system' => true, 'is_staff' => false, 'permissions' => null, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // Migra los permisos ya guardados en site_settings (si el super_admin
        // ya había configurado algo en /admin/role-permissions antes de esta
        // migración) hacia las filas recién creadas, en vez de perderlos.
        $siteSetting = DB::table('site_settings')->where('key', 'admin_role_permissions')->first();
        if ($siteSetting) {
            $saved = json_decode($siteSetting->value, true) ?? [];
            foreach (['admin', 'editor'] as $slug) {
                if (isset($saved[$slug])) {
                    DB::table('roles')->where('slug', $slug)->update([
                        'permissions' => json_encode(array_replace($allFeatures, $saved[$slug])),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
