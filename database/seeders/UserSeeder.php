<?php

namespace Database\Seeders;

use App\Models\Empleado;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Crea cuentas de login SOLO para quienes deben iniciar sesión en CajaFlow:
     * admin/gerente, secretaria y cajeros. Cocineros y seguridad quedan como
     * `empleados` para efectos de planilla, sin cuenta de usuario.
     *
     * IMPORTANTE: las contraseñas aquí son temporales de desarrollo.
     * Cámbialas antes de usar esto en producción, o mejor, fuerza un cambio
     * de contraseña en el primer login real de cada usuario.
     */
    private const USUARIOS = [
        ['empleado' => ['Carlos Omar', 'Palacios Casco'], 'email' => 'carlos@cajaflow.test', 'role' => 'admin'],
        ['empleado' => ['Claudia Aracely', 'Gomez Hernandez'], 'email' => 'claudia@cajaflow.test', 'role' => 'admin'],
        ['empleado' => ['Marcos Andres', 'Enamorado Gomez'], 'email' => 'marcos@cajaflow.test', 'role' => 'admin'],
        ['empleado' => ['Maria Roxana', 'Hernandez Rivera'], 'email' => 'roxana@cajaflow.test', 'role' => 'secretaria'],
        ['empleado' => ['Marlene Adalhitza', 'Romero Ham'], 'email' => 'marlene@cajaflow.test', 'role' => 'cajero'],
        ['empleado' => ['Ivis Carolina', 'Bu'], 'email' => 'ivis@cajaflow.test', 'role' => 'cajero'],
    ];

    public function run(): void
    {
        foreach (self::USUARIOS as $data) {
            [$nombre, $apellido] = $data['empleado'];
            $empleado = Empleado::where('nombre', $nombre)->where('apellido', $apellido)->first();

            if (! $empleado) {
                $this->command->warn("Empleado no encontrado para usuario {$data['email']} — corre EmpleadoSeeder primero.");
                continue;
            }

            User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $empleado->nombreCompleto(),
                    'password' => Hash::make('cajaflow2026'), // temporal, cambiar en primer login
                    'role' => $data['role'],
                    'empleado_id' => $empleado->id,
                    'activo' => true,
                    'email_verified_at' => now(),
                ]
            );
        }

        $this->command->info(count(self::USUARIOS) . ' usuarios sembrados. Password temporal: cajaflow2026');
    }
}
