<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Empleado extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'apellido',
        'identidad',
        'cargo',
        'fecha_ingreso',
        'sueldo_quincenal',
        'telefono',
        'direccion',
        'foto_path',
        'activo',
    ];

    protected $casts = [
        'fecha_ingreso' => 'date',
        'sueldo_quincenal' => 'decimal:2',
        'activo' => 'boolean',
    ];

    public function usuario(): HasOne
    {
        return $this->hasOne(User::class, 'empleado_id');
    }

    public function cierresCaja(): BelongsToMany
    {
        return $this->belongsToMany(CierreCaja::class, 'cierre_empleados_turno');
    }

    public function vales(): HasMany
    {
        return $this->hasMany(Vale::class);
    }

    public function prestamos(): HasMany
    {
        return $this->hasMany(Prestamo::class);
    }

    public function prestamoActivo(): HasOne
    {
        return $this->hasOne(Prestamo::class)->where('estado', 'activo')->latestOfMany();
    }

    public function comprasTienda(): HasMany
    {
        return $this->hasMany(CompraTienda::class);
    }

    public function llegadasTarde(): HasMany
    {
        return $this->hasMany(LlegadaTarde::class);
    }

    public function planillaDetalles(): HasMany
    {
        return $this->hasMany(PlanillaDetalle::class);
    }

    public function pagosPlanilla(): HasMany
    {
        return $this->hasMany(PagoPlanilla::class);
    }

    public function horariosTurno(): HasMany
    {
        return $this->hasMany(HorarioTurno::class);
    }

    public function nombreCompleto(): string
    {
        return trim("{$this->nombre} {$this->apellido}");
    }

    /** Suma de todas las planilla_detalles con saldo pendiente > 0. */
    public function saldoPendienteTotal(): float
    {
        return (float) $this->planillaDetalles()
            ->where('estado_pago', '!=', 'pagado')
            ->sum('saldo_pendiente');
    }
}
