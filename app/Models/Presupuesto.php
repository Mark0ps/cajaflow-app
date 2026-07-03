<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Presupuesto extends Model
{
    use HasFactory;

    protected $fillable = ['categoria', 'mes', 'anio', 'monto_presupuestado'];

    protected $casts = [
        'monto_presupuestado' => 'decimal:2',
    ];

    public function scopeDelPeriodo($query, int $mes, int $anio)
    {
        return $query->where('mes', $mes)->where('anio', $anio);
    }
}
