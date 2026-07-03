<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LlegadaTarde extends Model
{
    use HasFactory;

    protected $table = 'llegadas_tarde';

    protected $fillable = ['empleado_id', 'fecha', 'minutos_tarde', 'valor_deduccion', 'planilla_detalle_id'];

    protected $casts = [
        'fecha' => 'date',
        'valor_deduccion' => 'decimal:2',
    ];

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    public function planillaDetalle(): BelongsTo
    {
        return $this->belongsTo(PlanillaDetalle::class);
    }

    public function scopeNoAplicadas($query)
    {
        return $query->whereNull('planilla_detalle_id');
    }
}
