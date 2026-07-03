<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prestamo extends Model
{
    use HasFactory;

    protected $fillable = [
        'empleado_id',
        'monto_original',
        'saldo_pendiente',
        'fecha_otorgado',
        'motivo',
        'metodo_cobro',
        'monto_cuota',
        'estado',
    ];

    protected $casts = [
        'monto_original' => 'decimal:2',
        'saldo_pendiente' => 'decimal:2',
        'monto_cuota' => 'decimal:2',
        'fecha_otorgado' => 'date',
    ];

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    public function abonos(): HasMany
    {
        return $this->hasMany(PrestamoAbono::class);
    }

    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }

    /** Aplica un abono y marca el préstamo como pagado si el saldo llega a 0. */
    public function aplicarAbono(float $monto, ?int $planillaDetalleId = null): PrestamoAbono
    {
        $monto = min($monto, (float) $this->saldo_pendiente);

        $abono = $this->abonos()->create([
            'planilla_detalle_id' => $planillaDetalleId,
            'monto' => $monto,
            'fecha' => now()->toDateString(),
        ]);

        $this->saldo_pendiente -= $monto;
        if ($this->saldo_pendiente <= 0) {
            $this->saldo_pendiente = 0;
            $this->estado = 'pagado';
        }
        $this->save();

        return $abono;
    }
}
