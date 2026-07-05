<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compras_tienda', function (Blueprint $table) {
            $table->enum('tipo', ['compra_credito', 'cobro_adicional'])
                ->default('compra_credito')
                ->after('empleado_id');
            $table->string('motivo')->nullable()->after('descripcion');
        });
    }

    public function down(): void
    {
        Schema::table('compras_tienda', function (Blueprint $table) {
            $table->dropColumn(['tipo', 'motivo']);
        });
    }
};
