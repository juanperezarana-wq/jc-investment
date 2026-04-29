<?php
// database/migrations/2025_01_01_000001_create_usuarios_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            $table->string('nombres');
            $table->string('apellidos');
            $table->string('tipo_documento');           // CC, Pasaporte, CE
            $table->string('numero_documento')->unique();
            $table->string('email')->unique();
            $table->string('telefono');
            $table->string('ciudad');
            $table->string('password');
            $table->enum('rol', ['cliente', 'admin', 'analista'])->default('cliente');
            $table->boolean('activo')->default(true);
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
