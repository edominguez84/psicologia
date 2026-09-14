<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('payment_method')->nullable()->after('patient_note');
            // unpaid: recién creada, sin acción todavía.
            // reported: el paciente avisó que ya pagó (transferencia) y está
            //           por confirmarse manualmente, o pagó vía Wompi cuando
            //           haya integración real.
            // confirmed: el super_admin verificó el pago (o Wompi lo
            //            confirmó automáticamente).
            $table->string('payment_status')->default('unpaid')->after('payment_method');
            // Referencia externa del pago: vacía en transferencia bancaria
            // (el aviso llega por WhatsApp, no se sube comprobante al
            // sistema); se usará para el id de transacción de Wompi cuando
            // haya integración real de cobro.
            $table->string('payment_reference')->nullable()->after('payment_status');
            // Monto a pagar mostrado al paciente: el de la promoción elegida
            // si aplica, o null si la cita no tiene un monto asociado
            // todavía (v1: el pago es informativo/manual, no bloquea la cita).
            $table->decimal('amount', 8, 2)->nullable()->after('payment_reference');
            $table->foreignId('promotion_id')->nullable()->after('amount')
                ->constrained('promotions')->nullOnDelete();

            $table->index('payment_status');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('promotion_id');
            $table->dropColumn(['payment_method', 'payment_status', 'payment_reference', 'amount']);
        });
    }
};
