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

    /**
     * Editar un gasto propio (proveedor, descripción, factura, valor) mientras
     * el turno sigue abierto. Distinto de update(): ese es para que Secretaria
     * complete un N° de factura pendiente en cualquier momento, sin importar
     * el estado del cierre.
     */
    public function editarPropio(User $user, Gasto $gasto): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isCajero()
            && $gasto->cierreCaja
            && $gasto->cierreCaja->user_id === $user->id
            && $gasto->cierreCaja->estado === 'abierto';
    }
}
