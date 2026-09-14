<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->unsignedTinyInteger('rating')->nullable()->after('text');
        });

        Schema::table('users', function (Blueprint $table) {
            // Cuenta los intentos de publicar un testimonio con contenido
            // que infringe las normas (ver App\Support\ProfanityFilter); al
            // llegar al umbral configurado, la cuenta se banea
            // automáticamente (ver PatientTestimonialController).
            $table->unsignedTinyInteger('profanity_strikes')->default(0)->after('municipality');
        });
    }

    public function down(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->dropColumn('rating');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('profanity_strikes');
        });
    }
};
