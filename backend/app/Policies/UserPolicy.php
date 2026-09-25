<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('usuarios.ver');
    }

    public function view(User $user, User $model): bool
    {
        return $user->can('usuarios.ver');
    }

    public function create(User $user): bool
    {
        return $user->can('usuarios.crear');
    }

    public function update(User $user, User $model): bool
    {
        return $user->can('usuarios.actualizar') && ! $user->is($model);
    }

    public function delete(User $user, User $model): bool
    {
        return $user->can('usuarios.eliminar') && ! $user->is($model);
    }

    public function assignRoles(User $user, User $model): bool
    {
        return $user->can('usuarios.roles_asignar') && ! $user->is($model);
    }
}
