<?php

namespace App\Policies;

use App\Models\Gasto;
use App\Models\User;

class GastoPolicy
{
    public function viewAny(User $user): bool
    {
        // Admin y Secretaria ven todos; Cajero solo ve los de sus propios cierres
        // (el controller filtra por cierre_caja_id -> user_id en ese caso).
        return true;
    }

    /** Gasto ligado a un cierre de caja (cajero registrando su turno). */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isCajero();
    }

    /** Gasto externo del negocio, sin cierre asociado (presupuesto empresarial). */
    public function createExterno(User $user): bool
    {
        return $user->isAdmin() || $user->isSecretaria();
    }

    /** Editar número de factura pendiente. */
    public function update(User $user, Gasto $gasto): bool
    {
        return $user->isAdmin() || $user->isSecretaria();
    }

    public function delete(User $user, Gasto $gasto): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Un cajero solo puede borrar un gasto propio mientras su cierre siga abierto.
        return $user->isCajero()
            && $gasto->cierreCaja
            && $gasto->cierreCaja->user_id === $user->id
            && $gasto->cierreCaja->estado === 'abierto';
    }
}
