<?php

namespace App\Policies;

use App\Models\PagoPlanilla;
use App\Models\User;

class PagoPlanillaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, PagoPlanilla $pago): bool
    {
        // Un empleado con cuenta de usuario puede ver sus propios pagos
        // (útil para tu propio caso: ver tu estado de cuenta como admin/empleado).
        if ($user->isAdmin()) {
            return true;
        }

        return $user->empleado_id !== null && $user->empleado_id === $pago->empleado_id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }
}
