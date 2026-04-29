<?php
// database/migrations/2025_01_01_000002_create_tipos_prestamo_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ── TIPOS DE PRÉSTAMO ──────────────────────────
        Schema::create('tipos_prestamo', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');                   // Personal, Negocio, Vivienda, Libre inversión
            $table->text('descripcion')->nullable();
            $table->decimal('tasa_mensual', 5, 2);     // Ej: 1.50 = 1.5%
            $table->integer('plazo_min_meses');         // 6
            $table->integer('plazo_max_meses');         // 36
            $table->decimal('monto_min', 15, 2);        // 500000
            $table->decimal('monto_max', 15, 2);        // 50000000
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // ── SOLICITUDES DE PRÉSTAMO ────────────────────
        Schema::create('solicitudes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();         // SOL-2025-001
            $table->foreignId('usuario_id')->constrained('usuarios')->onDelete('cascade');
            $table->foreignId('tipo_prestamo_id')->constrained('tipos_prestamo');
            $table->decimal('monto_solicitado', 15, 2);
            $table->integer('plazo_meses');
            $table->decimal('tasa_mensual', 5, 2);
            $table->decimal('cuota_mensual', 15, 2)->nullable();
            $table->decimal('total_a_pagar', 15, 2)->nullable();
            $table->string('proposito');
            $table->decimal('ingresos_mensuales', 15, 2);
            $table->string('ocupacion');
            $table->enum('estado', [
                'pendiente',
                'en_revision',
                'aprobado',
                'rechazado',
                'cancelado'
            ])->default('pendiente');
            $table->text('comentario_analista')->nullable();
            $table->text('motivo_rechazo')->nullable();
            $table->foreignId('revisado_por')->nullable()->constrained('usuarios');
            $table->timestamp('fecha_revision')->nullable();
            $table->string('documento_cedula')->nullable();     // ruta del archivo
            $table->string('documento_ingresos')->nullable();
            $table->timestamps();
        });

        // ── PAGOS ──────────────────────────────────────
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_id')->constrained('solicitudes')->onDelete('cascade');
            $table->foreignId('usuario_id')->constrained('usuarios');
            $table->integer('numero_cuota');            // 1, 2, 3...
            $table->decimal('monto', 15, 2);
            $table->decimal('capital', 15, 2);
            $table->decimal('interes', 15, 2);
            $table->date('fecha_vencimiento');
            $table->date('fecha_pago')->nullable();
            $table->enum('estado', ['pendiente', 'pagado', 'vencido'])->default('pendiente');
            $table->string('metodo_pago')->nullable();  // Transferencia, PSE, efectivo
            $table->string('comprobante')->nullable();
            $table->timestamps();
        });

        // ── NOTIFICACIONES ─────────────────────────────
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->onDelete('cascade');
            $table->string('titulo');
            $table->text('mensaje');
            $table->string('tipo')->default('info');    // info, success, warning, error
            $table->boolean('leida')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
        Schema::dropIfExists('pagos');
        Schema::dropIfExists('solicitudes');
        Schema::dropIfExists('tipos_prestamo');
    }
};
