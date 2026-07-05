<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoEfectivo extends Model
{
    use HasFactory;

    protected $table = 'movimientos_efectivo';

    protected $fillable = [
        'cierre_caja_id',
        'tipo',
        'monto',
        'motivo',
        'registrado_por',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
    ];

    public function cierreCaja(): BelongsTo
    {
        return $this->belongsTo(CierreCaja::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
