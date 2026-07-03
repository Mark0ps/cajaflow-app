<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empleados', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('apellido');
            $table->string('identidad')->nullable()->unique();
            $table->enum('cargo', [
                'gerente',
                'administrador',
                'cajero_barista',
                'cocinero',
                'secretaria',
                'seguridad',
                'otro',
            ]);
            $table->date('fecha_ingreso');
            $table->decimal('sueldo_quincenal', 10, 2);
            $table->string('telefono')->nullable();
            $table->string('direccion')->nullable();
            $table->string('foto_path')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['activo', 'cargo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empleados');
    }
};
