<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            $table->string('contacto_nombre')->nullable()->after('nombre');
            $table->string('telefono')->nullable()->after('contacto_nombre');
            $table->string('direccion')->nullable()->after('telefono');
            $table->boolean('factura_nominal')->default(true)->after('activo');
        });
    }

    public function down(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            $table->dropColumn(['contacto_nombre', 'telefono', 'direccion', 'factura_nominal']);
        });
    }
};
