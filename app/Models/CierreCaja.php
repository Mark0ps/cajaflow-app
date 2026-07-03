<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CierreCaja extends Model
{
    use HasFactory;

    protected $table = 'cierres_caja';

    protected $fillable = [
        'fecha',
        'turno',
        'user_id',
        'monto_inicial',
        'efectivo',
        'tarjeta_credito',
        'transferencia',
        'total_ingreso',
        'venta_sistema_a2',
        'diferencia',
        'total_gastos',
        'total_vales',
        'efectivo_dia_venta',
        'estado',
        'observaciones',
        'revisado_por',
        'revisado_en',
    ];

    protected $casts = [
        'fecha' => 'date',
        'monto_inicial' => 'decimal:2',
        'efectivo' => 'decimal:2',
        'tarjeta_credito' => 'decimal:2',
        'transferencia' => 'decimal:2',
        'total_ingreso' => 'decimal:2',
        'venta_sistema_a2' => 'decimal:2',
        'diferencia' => 'decimal:2',
        'total_gastos' => 'decimal:2',
        'total_vales' => 'decimal:2',
        'efectivo_dia_venta' => 'decimal:2',
        'revisado_en' => 'datetime',
    ];

    public function cajero(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function revisadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revisado_por');
    }

    public function empleadosTurno(): BelongsToMany
    {
        return $this->belongsToMany(Empleado::class, 'cierre_empleados_turno');
    }

    public function gastos(): HasMany
    {
        return $this->hasMany(Gasto::class);
    }

    public function vales(): HasMany
    {
        return $this->hasMany(Vale::class);
    }

    public function scopeDelDia($query, $fecha)
    {
        return $query->whereDate('fecha', $fecha);
    }

    /**
     * Recalcula totales e diferencia contra el sistema A2 Food.
     * Se recomienda invocarlo desde CierreCajaService, no directamente en el modelo.
     */
    public function recalcularTotales(): void
    {
        $this->total_ingreso = $this->efectivo + $this->tarjeta_credito + $this->transferencia;
        $this->total_gastos = $this->gastos()->sum('valor');
        $this->total_vales = $this->vales()->sum('monto');

        // efectivo es el monto NETO que queda físico en caja al cierre (ya
        // pagados los gastos/vales desde la gaveta) — para comparar contra la
        // venta bruta reportada por A2 Food, se reconstruye el bruto sumando
        // de vuelta lo que salió de la gaveta en gastos y vales.
        $this->diferencia = $this->venta_sistema_a2 !== null
            ? round(($this->total_ingreso + $this->total_gastos + $this->total_vales) - $this->venta_sistema_a2, 2)
            : 0;

        $this->efectivo_dia_venta = $this->efectivo;
    }
}
