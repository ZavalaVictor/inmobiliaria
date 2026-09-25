<?php

namespace App\Actions\Users;

use App\Models\User;
use Illuminate\Support\Facades\DB;

final class SyncUserRolesAction
{
    /**
     * @param  array<int, string>  $roles
     */
    public function execute(User $user, array $roles): User
    {
        return DB::transaction(function () use ($user, $roles): User {
            $user->syncRoles($roles);

            return $user->fresh(['roles:id,name,guard_name']);
        });
    }
}
