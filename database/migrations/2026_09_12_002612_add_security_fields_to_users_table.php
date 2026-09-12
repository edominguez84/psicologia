<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Baneo: presencia de fecha = baneado. Más auditable que un booleano
            // (se sabe cuándo se baneó) y permite reactivar poniéndolo a null.
            $table->timestamp('banned_at')->nullable()->after('role');
            $table->string('banned_reason')->nullable()->after('banned_at');

            // 2FA: método elegido por el propio usuario. Null cae al fallback
            // obligatorio 'email'. 'totp' solo cuenta como método activo una
            // vez confirmado (two_factor_confirmed_at no nulo).
            $table->string('two_factor_method')->nullable()->after('remember_token');
            $table->text('two_factor_secret')->nullable()->after('two_factor_method');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_secret');
            $table->string('phone_number')->nullable()->after('two_factor_confirmed_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'banned_at',
                'banned_reason',
                'two_factor_method',
                'two_factor_secret',
                'two_factor_confirmed_at',
                'phone_number',
            ]);
        });
    }
};
