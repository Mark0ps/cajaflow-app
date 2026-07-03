<?php

namespace App\Policies;

use App\Models\CierreCaja;
use App\Models\User;

class CierreCajaPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // el filtrado por rol (solo "propios" del cajero) se hace en el controller/query
    }

    public function view(User $user, CierreCaja $cierre): bool
    {
        if ($user->isAdmin() || $user->isSecretaria()) {
            return true;
        }

        return $user->isCajero() && $cierre->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        // Solo cajeros abren su propio turno; Admin puede abrir turnos de soporte.
        return $user->isCajero() || $user->isAdmin();
    }

    public function update(User $user, CierreCaja $cierre): bool
    {
        if ($cierre->estado !== 'abierto') {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isCajero() && $cierre->user_id === $user->id;
    }

    public function cerrar(User $user, CierreCaja $cierre): bool
    {
        return $this->update($user, $cierre);
    }

    public function revisar(User $user, CierreCaja $cierre): bool
    {
        return ($user->isAdmin() || $user->isSecretaria()) && $cierre->estado === 'cerrado';
    }
}
