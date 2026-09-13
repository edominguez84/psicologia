<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_sections', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            // Imagen opcional; vive en storage/app/public/custom-sections/.
            $table->string('image_path')->nullable();
            // Orden entre sí (todas se renderizan al final de la landing,
            // justo antes de "Contacto", ordenadas por esta columna).
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_sections');
    }
};
