<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presupuestos', function (Blueprint $table) {
            $table->id();
            $table->string('categoria');
            $table->unsignedTinyInteger('mes');
            $table->unsignedSmallInteger('anio');
            $table->decimal('monto_presupuestado', 10, 2);
            $table->timestamps();

            $table->unique(['categoria', 'mes', 'anio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presupuestos');
    }
};
