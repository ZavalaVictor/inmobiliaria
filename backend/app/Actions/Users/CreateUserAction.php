<?php

namespace App\Actions\Users;

use App\Models\User;
use Illuminate\Support\Arr;

final class CreateUserAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(array $attributes): User
    {
        $user = User::create(Arr::only($attributes, [
            'nombres',
            'apellido_paterno',
            'apellido_materno',
            'email',
            'telefono',
            'password',
            'estado',
        ]));

        return $user->fresh();
    }
}
