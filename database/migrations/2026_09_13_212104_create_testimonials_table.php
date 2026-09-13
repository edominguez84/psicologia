<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            // Un testimonio activo por paciente (updateOrCreate sobre user_id).
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('text');
            // Nunca true por default: todo testimonio nuevo o editado exige
            // revisión antes de aparecer en la sección pública.
            $table->boolean('is_approved')->default(false);
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testimonials');
    }
};
