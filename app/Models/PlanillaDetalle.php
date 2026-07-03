<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanillaDetalle extends Model
{
    use HasFactory;

    protected $fillable = [
        'planilla_id', 'empleado_id',
        'sueldo_quincenal', 'sueldo_diario', 'dias_laborados',
        'horas_extras_cantidad', 'valor_hora_extra', 'horas_extras_valor',
        'salario_devengado', 'bonificaciones',
        'total_vales', 'total_compras_tienda', 'total_abono_prestamo', 'total_llegadas_tarde',
        'otras_deducciones', 'total_deducciones', 'total_a_pagar',
        'monto_pagado', 'saldo_pendiente', 'estado_pago',
    ];

    protected $casts = [
        'sueldo_quincenal' => 'decimal:2',
        'sueldo_diario' => 'decimal:2',
        'horas_extras_cantidad' => 'decimal:2',
        'valor_hora_extra' => 'decimal:2',
        'horas_extras_valor' => 'decimal:2',
        'salario_devengado' => 'decimal:2',
        'bonificaciones' => 'decimal:2',
        'total_vales' => 'decimal:2',
        'total_compras_tienda' => 'decimal:2',
        'total_abono_prestamo' => 'decimal:2',
        'total_llegadas_tarde' => 'decimal:2',
        'otras_deducciones' => 'decimal:2',
        'total_deducciones' => 'decimal:2',
        'total_a_pagar' => 'decimal:2',
        'monto_pagado' => 'decimal:2',
        'saldo_pendiente' => 'decimal:2',
    ];

    public function planilla(): BelongsTo
    {
        return $this->belongsTo(Planilla::class);
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    public function vales(): HasMany
    {
        return $this->hasMany(Vale::class);
    }

    public function comprasTienda(): HasMany
    {
        return $this->hasMany(CompraTienda::class);
    }

    public function llegadasTarde(): HasMany
    {
        return $this->hasMany(LlegadaTarde::class);
    }

    public function prestamoAbonos(): HasMany
    {
        return $this->hasMany(PrestamoAbono::class);
    }

    /** Pagos que han cubierto (total o parcialmente) esta quincena. */
    public function pagosAplicados(): BelongsToMany
    {
        return $this->belongsToMany(PagoPlanilla::class, 'pago_planilla_detalle')
            ->withPivot('monto_aplicado')
            ->withTimestamps();
    }

    public function scopePendientesDe($query, int $empleadoId)
    {
        return $query->where('empleado_id', $empleadoId)
            ->where('estado_pago', '!=', 'pagado')
            ->orderBy('created_at');
    }

    /**
     * Recalcula total_a_pagar según salario_devengado + horas_extras_valor +
     * bonificaciones - total_deducciones. Si el registro aún no tiene pagos
     * aplicados, también actualiza saldo_pendiente para que quede en sync.
     */
    public function recalcularTotal(): void
    {
        $this->total_a_pagar = max(0, round(
            (float) $this->salario_devengado
            + (float) $this->horas_extras_valor
            + (float) $this->bonificaciones
            - (float) $this->total_deducciones,
            2
        ));

        if ((float) $this->monto_pagado <= 0) {
            $this->saldo_pendiente = $this->total_a_pagar;
        }

        $this->save();
    }

    /**
     * Aplica un monto de un pago a esta quincena y actualiza sus estados.
     * Debe llamarse dentro de una transacción (ver PagoPlanillaService).
     */
    public function aplicarPago(float $monto): void
    {
        $this->monto_pagado += $monto;
        $this->saldo_pendiente = max(0, $this->total_a_pagar - $this->monto_pagado);

        $this->estado_pago = $this->saldo_pendiente <= 0
            ? 'pagado'
            : ($this->monto_pagado > 0 ? 'parcial' : 'pendiente');

        $this->save();
    }
}
