<?php
// database/seeders/DatabaseSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\{Usuario, TipoPrestamo};

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── USUARIOS ───────────────────────────────────
        // Admin
        Usuario::create([
            'nombres'          => 'Admin',
            'apellidos'        => 'JC Investments',
            'tipo_documento'   => 'CC',
            'numero_documento' => '1000000001',
            'email'            => 'admin@jcinvestments.com',
            'telefono'         => '300 000 0001',
            'ciudad'           => 'Bogotá',
            'password'         => Hash::make('admin123'),
            'rol'              => 'admin',
        ]);

        // Analista
        Usuario::create([
            'nombres'          => 'Laura',
            'apellidos'        => 'Moreno Ruiz',
            'tipo_documento'   => 'CC',
            'numero_documento' => '1000000002',
            'email'            => 'analista@jcinvestments.com',
            'telefono'         => '300 000 0002',
            'ciudad'           => 'Medellín',
            'password'         => Hash::make('analista123'),
            'rol'              => 'analista',
        ]);

        // Cliente demo
        Usuario::create([
            'nombres'          => 'Juan Carlos',
            'apellidos'        => 'García López',
            'tipo_documento'   => 'CC',
            'numero_documento' => '1234567890',
            'email'            => 'cliente@demo.com',
            'telefono'         => '310 123 4567',
            'ciudad'           => 'Cali',
            'password'         => Hash::make('cliente123'),
            'rol'              => 'cliente',
        ]);

        // ── TIPOS DE PRÉSTAMO ──────────────────────────
        $tipos = [
            [
                'nombre'          => 'Préstamo personal',
                'descripcion'     => 'Para gastos personales, viajes o emergencias.',
                'tasa_mensual'    => 1.50,
                'plazo_min_meses' => 3,
                'plazo_max_meses' => 36,
                'monto_min'       => 500000,
                'monto_max'       => 20000000,
                'activo'          => true,
            ],
            [
                'nombre'          => 'Préstamo de negocio',
                'descripcion'     => 'Capital de trabajo para tu empresa o emprendimiento.',
                'tasa_mensual'    => 1.80,
                'plazo_min_meses' => 6,
                'plazo_max_meses' => 48,
                'monto_min'       => 1000000,
                'monto_max'       => 100000000,
                'activo'          => true,
            ],
            [
                'nombre'          => 'Préstamo de vivienda',
                'descripcion'     => 'Para compra, remodelación o mejora de tu hogar.',
                'tasa_mensual'    => 1.20,
                'plazo_min_meses' => 12,
                'plazo_max_meses' => 60,
                'monto_min'       => 5000000,
                'monto_max'       => 200000000,
                'activo'          => true,
            ],
            [
                'nombre'          => 'Libre inversión',
                'descripcion'     => 'Para cualquier propósito. Sin restricciones de uso.',
                'tasa_mensual'    => 2.00,
                'plazo_min_meses' => 3,
                'plazo_max_meses' => 24,
                'monto_min'       => 300000,
                'monto_max'       => 10000000,
                'activo'          => true,
            ],
        ];

        foreach ($tipos as $tipo) {
            TipoPrestamo::create($tipo);
        }

        $this->command->info('✅ Datos de prueba creados:');
        $this->command->info('   admin@jcinvestments.com  / admin123');
        $this->command->info('   analista@jcinvestments.com / analista123');
        $this->command->info('   cliente@demo.com         / cliente123');
    }
}
