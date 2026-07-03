<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Gasto extends Model
{
    use HasFactory;

    protected $fillable = [
        'cierre_caja_id',
        'proveedor_id',
        'proveedor_nombre_libre',
        'descripcion',
        'numero_factura',
        'factura_pendiente',
        'tipo_pago',
        'valor',
        'es_externo',
        'agregado_por',
    ];

    protected $casts = [
        'factura_pendiente' => 'boolean',
        'es_externo' => 'boolean',
        'valor' => 'decimal:2',
    ];

    public function cierreCaja(): BelongsTo
    {
        return $this->belongsTo(CierreCaja::class);
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function agregadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agregado_por');
    }

    public function nombreProveedor(): string
    {
        return $this->proveedor?->nombre ?? $this->proveedor_nombre_libre ?? 'N/A';
    }

    public function scopeExternos($query)
    {
        return $query->where('es_externo', true);
    }

    public function scopeFacturaPendiente($query)
    {
        return $query->where('factura_pendiente', true);
    }
}
