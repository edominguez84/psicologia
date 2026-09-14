<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            // Respuestas a los campos personalizados que el admin defina en
            // /admin/contact (array de {label, value}), además de los
            // campos fijos ya existentes (name/email/phone/subject/message).
            $table->json('custom_fields')->nullable()->after('preferred_contact');
        });
    }

    public function down(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->dropColumn('custom_fields');
        });
    }
};
