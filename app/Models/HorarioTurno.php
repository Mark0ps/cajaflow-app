<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HorarioTurno extends Model
{
    use HasFactory;
    
    protected $table = 'horarios_turno';

    protected $fillable = ['empleado_id', 'dia_semana', 'turno', 'hora_inicio', 'hora_fin'];

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }
}
