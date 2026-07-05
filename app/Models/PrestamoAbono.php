<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrestamoAbono extends Model
{
    use HasFactory;

    protected $fillable = ['prestamo_id', 'planilla_detalle_id', 'monto', 'motivo', 'fecha'];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha' => 'date',
    ];

    public function prestamo(): BelongsTo
    {
        return $this->belongsTo(Prestamo::class);
    }

    public function planillaDetalle(): BelongsTo
    {
        return $this->belongsTo(PlanillaDetalle::class);
    }
}
