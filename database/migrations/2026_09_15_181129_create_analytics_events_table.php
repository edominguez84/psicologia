<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            // 'page_view': una carga de la home. 'social_click': clic en un
            // link de red social del footer. $meta guarda detalle específico
            // del tipo (p. ej. {"network": "instagram"} para social_click) —
            // así el catálogo de tipos de evento puede crecer sin nuevas
            // columnas ni migraciones.
            $table->string('type');
            $table->json('meta')->nullable();
            $table->string('session_id', 64)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_events');
    }
};
