<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('planilla_detalles', function (Blueprint $table) {
            $table->decimal('horas_extras_cantidad', 10, 2)->default(0)->after('dias_laborados');
            $table->decimal('valor_hora_extra', 10, 2)->default(0)->after('horas_extras_cantidad');
            $table->decimal('bonificaciones', 10, 2)->default(0)->after('horas_extras_valor');
        });
    }

    public function down(): void
    {
        Schema::table('planilla_detalles', function (Blueprint $table) {
            $table->dropColumn(['horas_extras_cantidad', 'valor_hora_extra', 'bonificaciones']);
        });
    }
};
