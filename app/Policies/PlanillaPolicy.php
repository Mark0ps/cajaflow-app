<?php

namespace App\Policies;

use App\Models\Planilla;
use App\Models\User;

class PlanillaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Planilla $planilla): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Planilla $planilla): bool
    {
        return $user->isAdmin() && $planilla->estado === 'borrador';
    }

    public function cerrar(User $user, Planilla $planilla): bool
    {
        return $user->isAdmin() && $planilla->estado === 'borrador';
    }

    public function eliminar(User $user, Planilla $planilla): bool
    {
        return $user->isAdmin() && $planilla->estado === 'borrador';
    }
}
