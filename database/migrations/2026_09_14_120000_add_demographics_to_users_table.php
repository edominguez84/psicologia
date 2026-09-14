<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 'sex' es un string libre validado en la capa de aplicación
            // contra ['male', 'female', 'other'] (ver StoreRegistrationRequest),
            // no un enum de columna — mismo criterio ya usado para 'role'.
            $table->string('sex')->nullable()->after('birth_date');
            // 'department'/'municipality' guardan la etiqueta legible (p. ej.
            // "San Salvador"), no la key interna del catálogo — así el dato
            // sigue siendo legible directamente en la base de datos aunque el
            // catálogo de App\Support\ElSalvadorLocations cambie de claves.
            $table->string('department')->nullable()->after('sex');
            $table->string('municipality')->nullable()->after('department');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['sex', 'department', 'municipality']);
        });
    }
};
