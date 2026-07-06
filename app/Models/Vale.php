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
        'fecha_emision', 'comprobante_path', 'registrado_por',
        'aplicado_en_planilla', 'planilla_detalle_id',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha_emision' => 'date',
        'aplicado_en_planilla' => 'boolean',
    ];

    protected $appends = ['comprobante_url'];

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

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function getComprobanteUrlAttribute(): ?string
    {
        return $this->comprobante_path ? asset('storage/' . $this->comprobante_path) : null;
    }

    public function scopeNoAplicados($query)
    {
        return $query->where('aplicado_en_planilla', false);
    }

    /** Vale libre: no depende de un turno de caja abierto. */
    public function scopeLibres($query)
    {
        return $query->whereNull('cierre_caja_id');
    }
}
