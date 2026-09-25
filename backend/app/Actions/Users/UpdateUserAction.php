<?php

namespace App\Actions\Users;

use App\Models\User;
use Illuminate\Support\Arr;

final class UpdateUserAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(User $user, array $attributes): User
    {
        $user->fill(Arr::only($attributes, [
            'nombres',
            'apellido_paterno',
            'apellido_materno',
            'email',
            'telefono',
            'estado',
        ]));
        $user->save();

        return $user->fresh(['roles:id,name,guard_name']);
    }
}
