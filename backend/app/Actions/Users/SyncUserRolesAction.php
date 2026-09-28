<?php

namespace App\Actions\Users;

use App\Models\User;
use App\Services\BitacoraService;
use Illuminate\Support\Facades\DB;

final class SyncUserRolesAction
{
    public function __construct(private readonly BitacoraService $bitacora) {}

    /**
     * @param  array<int, string>  $roles
     */
    public function execute(User $user, array $roles, ?User $actor = null): User
    {
        return DB::transaction(function () use ($user, $roles, $actor): User {
            $before = $user->roles()->pluck('name')->values()->all();
            $user->syncRoles($roles);
            $after = $user->fresh('roles')->roles->pluck('name')->values()->all();

            $this->bitacora->record(
                $actor,
                'usuario_roles_actualizados',
                'usuario',
                $user->getKey(),
                'Roles de usuario actualizados.',
                ['roles' => $before],
                ['roles' => $after],
            );

            return $user->fresh(['roles:id,name,guard_name']);
        });
    }
}
