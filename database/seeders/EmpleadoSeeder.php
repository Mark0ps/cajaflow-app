<?php

namespace Database\Seeders;

use App\Models\Empleado;
use Illuminate\Database\Seeder;

class EmpleadoSeeder extends Seeder
{
    /**
     * Migrado de las hojas "LISTA DEL PERSONAL", "PLANILLA 1" y "CONTADOR".
     * Fechas de ingreso convertidas de número de serie Excel a fecha real.
     * Los que no tenían fecha de ingreso en el Excel (Secretaria, Seguridad)
     * quedan con la fecha de hoy como placeholder — ajústalas manualmente.
     */
    private const EMPLEADOS = [
        // Administración (tienen sueldo, según PLANILLA 1 / CONTADOR)
        ['nombre' => 'Carlos Omar', 'apellido' => 'Palacios Casco', 'identidad' => '1807-1977-01428', 'cargo' => 'gerente', 'fecha_ingreso' => '2019-11-29', 'sueldo_quincenal' => 7500],
        ['nombre' => 'Claudia Aracely', 'apellido' => 'Gomez Hernandez', 'identidad' => '0801-1975-20127', 'cargo' => 'administrador', 'fecha_ingreso' => '2019-11-29', 'sueldo_quincenal' => 5000],
        ['nombre' => 'Marcos Andres', 'apellido' => 'Enamorado Gomez', 'identidad' => null, 'cargo' => 'administrador', 'fecha_ingreso' => '2019-11-29', 'sueldo_quincenal' => 5000],

        // Cajeros/Baristas
        ['nombre' => 'Marlene Adalhitza', 'apellido' => 'Romero Ham', 'identidad' => null, 'cargo' => 'cajero_barista', 'fecha_ingreso' => '2025-11-02', 'sueldo_quincenal' => 4500],
        ['nombre' => 'Ivis Carolina', 'apellido' => 'Bu', 'identidad' => null, 'cargo' => 'cajero_barista', 'fecha_ingreso' => '2024-05-02', 'sueldo_quincenal' => 5500],

        // Cocina
        ['nombre' => 'Iris Amanda', 'apellido' => 'Garcia Chica', 'identidad' => null, 'cargo' => 'cocinero', 'fecha_ingreso' => '2023-11-13', 'sueldo_quincenal' => 4500],
        ['nombre' => 'Maria Esther', 'apellido' => 'Perez Bejarano', 'identidad' => null, 'cargo' => 'cocinero', 'fecha_ingreso' => '2023-05-01', 'sueldo_quincenal' => 4500],
        ['nombre' => 'Delfis Suyapa', 'apellido' => 'Trochez Pineda', 'identidad' => null, 'cargo' => 'cocinero', 'fecha_ingreso' => '2024-06-17', 'sueldo_quincenal' => 4500],

        // Otros roles (hoja CONTADOR) — sin fecha de ingreso registrada en el Excel
        ['nombre' => 'Maria Roxana', 'apellido' => 'Hernandez Rivera', 'identidad' => null, 'cargo' => 'secretaria', 'fecha_ingreso' => null, 'sueldo_quincenal' => 5000],
        ['nombre' => 'Carlos Alberto', 'apellido' => 'Hernandez', 'identidad' => null, 'cargo' => 'seguridad', 'fecha_ingreso' => null, 'sueldo_quincenal' => 7500],
    ];

    public function run(): void
    {
        foreach (self::EMPLEADOS as $data) {
            Empleado::firstOrCreate(
                ['nombre' => $data['nombre'], 'apellido' => $data['apellido']],
                [
                    'identidad' => $data['identidad'],
                    'cargo' => $data['cargo'],
                    'fecha_ingreso' => $data['fecha_ingreso'] ?? now()->toDateString(),
                    'sueldo_quincenal' => $data['sueldo_quincenal'],
                    'activo' => true,
                ]
            );
        }

        $this->command->info(count(self::EMPLEADOS) . ' empleados sembrados.');
        $this->command->warn('Revisa: identidad e ingreso de Marcos, y fecha_ingreso de Secretaria/Seguridad (no estaban en el Excel).');
    }
}
