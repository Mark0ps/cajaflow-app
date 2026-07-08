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
        'fecha_emision',
        'descripcion',
        'numero_factura',
        'factura_pendiente',
        'tipo_pago',
        'valor',
        'es_externo',
        'categoria',
        'agregado_por',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
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

    /**
     * Un proveedor con factura_nominal = false es informal: nunca emite
     * factura, así que numero_factura se fuerza a "N/A" y no queda
     * pendiente, sin importar lo que se haya escrito en el formulario.
     */
    public static function normalizarFacturaPorProveedor(array $data, ?int $proveedorId): array
    {
        $proveedor = $proveedorId ? Proveedor::find($proveedorId) : null;

        if ($proveedor && ! $proveedor->factura_nominal) {
            $data['numero_factura'] = 'N/A';
            $data['factura_pendiente'] = false;
        }

        return $data;
    }
}
