<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Asume que la tabla `users` ya existe (Laravel/Sanctum, igual que en AutoSys).
    // Si tu convención de nombres es `usuarios`, renombra esta migración y las FKs
    // de las demás tablas de `user_id`/`users` a `usuario_id`/`usuarios`.
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'secretaria', 'cajero'])->default('cajero')->after('email');
            $table->foreignId('empleado_id')->nullable()->after('role')
                ->constrained('empleados')->nullOnDelete();
            $table->boolean('activo')->default(true)->after('empleado_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('empleado_id');
            $table->dropColumn(['role', 'activo']);
        });
    }
};
