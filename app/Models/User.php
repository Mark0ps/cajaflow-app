<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

// NOTA: este archivo asume que reemplazas/mergeas con tu app/Models/User.php
// existente (el que ya usa Sanctum en AutoSys). Solo se agregan los campos
// y relaciones nuevas de CajaFlow (role, empleado_id, activo).
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'username',
        'password',
        'role',
        'empleado_id',
        'activo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'activo' => 'boolean',
    ];

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    public function cierresCaja(): HasMany
    {
        return $this->hasMany(CierreCaja::class);
    }

    public function cierresRevisados(): HasMany
    {
        return $this->hasMany(CierreCaja::class, 'revisado_por');
    }

    public function gastosAgregados(): HasMany
    {
        return $this->hasMany(Gasto::class, 'agregado_por');
    }

    public function planillasGeneradas(): HasMany
    {
        return $this->hasMany(Planilla::class, 'generada_por');
    }

    public function pagosRegistrados(): HasMany
    {
        return $this->hasMany(PagoPlanilla::class, 'registrado_por');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isSecretaria(): bool
    {
        return $this->role === 'secretaria';
    }

    public function isCajero(): bool
    {
        return $this->role === 'cajero';
    }
}
