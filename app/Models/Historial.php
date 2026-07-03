<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Historial extends Model
{
    use HasFactory;

    protected $table = 'historial';

    protected $fillable = ['tabla', 'registro_id', 'accion', 'user_id', 'datos_antes', 'datos_despues'];

    protected $casts = [
        'datos_antes' => 'array',
        'datos_despues' => 'array',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeDe($query, string $tabla, int $registroId)
    {
        return $query->where('tabla', $tabla)->where('registro_id', $registroId)->latest();
    }
}
