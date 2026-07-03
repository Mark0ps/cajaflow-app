<?php

namespace App\Policies;

use App\Models\Proveedor;
use App\Models\User;

class ProveedorPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    /** Creación rápida: cualquier rol autenticado puede agregar un proveedor nuevo al catálogo. */
    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Proveedor $proveedor): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Proveedor $proveedor): bool
    {
        return $user->isAdmin();
    }
}
