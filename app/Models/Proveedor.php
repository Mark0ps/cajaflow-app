<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proveedor extends Model
{
    use HasFactory;

    protected $table = 'proveedores';

    protected $fillable = ['nombre', 'contacto_nombre', 'telefono', 'direccion', 'descripcion', 'activo', 'factura_nominal'];

    protected $casts = [
        'activo' => 'boolean',
        'factura_nominal' => 'boolean',
    ];

    public function gastos(): HasMany
    {
        return $this->hasMany(Gasto::class);
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}