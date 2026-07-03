<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Planilla extends Model
{
    use HasFactory;

    protected $fillable = [
        'anio', 'mes', 'quincena', 'periodo_inicio', 'periodo_fin',
        'estado', 'generada_por', 'cerrada_en',
    ];

    protected $casts = [
        'periodo_inicio' => 'date',
        'periodo_fin' => 'date',
        'cerrada_en' => 'datetime',
    ];

    public function generadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generada_por');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(PlanillaDetalle::class);
    }

    public function totalAPagar(): float
    {
        return (float) $this->detalles()->sum('total_a_pagar');
    }

    public function totalPendiente(): float
    {
        return (float) $this->detalles()->sum('saldo_pendiente');
    }
}
