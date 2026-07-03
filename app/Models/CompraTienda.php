<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompraTienda extends Model
{
    use HasFactory;

    protected $table = 'compras_tienda';

    protected $fillable = ['empleado_id', 'fecha', 'descripcion', 'valor', 'planilla_detalle_id'];

    protected $casts = [
        'fecha' => 'date',
        'valor' => 'decimal:2',
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
