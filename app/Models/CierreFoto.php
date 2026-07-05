<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CierreFoto extends Model
{
    protected $table = 'cierre_fotos';

    protected $fillable = ['cierre_caja_id', 'foto_path', 'descripcion', 'subido_por'];

    protected $appends = ['url'];

    public function cierreCaja(): BelongsTo
    {
        return $this->belongsTo(CierreCaja::class);
    }

    public function subidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subido_por');
    }

    protected function url(): Attribute
    {
        return Attribute::get(fn () => asset('storage/' . $this->foto_path));
    }
}
