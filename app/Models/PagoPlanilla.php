<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PagoPlanilla extends Model
{
    use HasFactory;

    protected $table = 'pagos_planilla';

    protected $fillable = [
        'empleado_id', 'fecha_pago', 'monto_total', 'metodo',
        'comprobante_path', 'registrado_por', 'notas',
    ];

    protected $casts = [
        'fecha_pago' => 'date',
        'monto_total' => 'decimal:2',
    ];

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    /** Quincenas (planilla_detalles) que este pago cubrió, total o parcialmente. */
    public function planillaDetalles(): BelongsToMany
    {
        return $this->belongsToMany(PlanillaDetalle::class, 'pago_planilla_detalle')
            ->withPivot('monto_aplicado')
            ->withTimestamps();
    }

    public function comprobanteUrl(): ?string
    {
        return $this->comprobante_path ? asset('storage/' . $this->comprobante_path) : null;
    }
}
