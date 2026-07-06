<?php

namespace App\Policies;

use App\Models\Prestamo;
use App\Models\User;

class PrestamoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Prestamo $prestamo): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->empleado_id !== null && $user->empleado_id === $prestamo->empleado_id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Prestamo $prestamo): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Prestamo $prestamo): bool
    {
        return $user->isAdmin();
    }
}
