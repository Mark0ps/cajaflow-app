<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('horarios_turno', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleados')->cascadeOnDelete();
            $table->enum('dia_semana', [
                'lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo',
            ]);
            $table->enum('turno', ['matutino', 'tarde', 'nocturno']);
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->timestamps();

            $table->index(['empleado_id', 'dia_semana']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('horarios_turno');
    }
};
