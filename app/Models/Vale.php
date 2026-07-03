<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vale extends Model
{
    use HasFactory;

    protected $fillable = [
        'cierre_caja_id', 'empleado_id', 'monto', 'descripcion',
        'aplicado_en_planilla', 'planilla_detalle_id',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'aplicado_en_planilla' => 'boolean',
    ];

    public function cierreCaja(): BelongsTo
    {
        return $this->belongsTo(CierreCaja::class);
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    public function planillaDetalle(): BelongsTo
    {
        return $this->belongsTo(PlanillaDetalle::class);
    }

    public function scopeNoAplicados($query)
    {
        return $query->where('aplicado_en_planilla', false);
    }
}
